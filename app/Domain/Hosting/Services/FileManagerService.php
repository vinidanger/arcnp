<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Todas as operações são síncronas — o Agent já responde na mesma
 * requisição (nada assíncrono aqui, ao contrário de SSL/backup).
 * Restrito a UMA raiz por vez (ver FileManagerPath no Agent): por
 * padrão public_html, ou — quando $root é informado — a árvore própria
 * de um domínio location=outside_public_html. $root só pode ser um
 * domínio que REALMENTE pertence à conta e está nessa location; quem
 * garante isso é este serviço, não o Agent (ele só valida formato).
 */
class FileManagerService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function list(HostingAccount $account, string $path, ?string $root = null): array
    {
        return $this->run($account, 'files.list', ['path' => $path], $root);
    }

    public function read(HostingAccount $account, string $path, ?string $root = null): array
    {
        return $this->run($account, 'files.read', ['path' => $path], $root);
    }

    public function write(HostingAccount $account, string $path, string $content, ?string $root = null): void
    {
        $this->assertUnderQuota($account);
        $this->run($account, 'files.write', ['path' => $path, 'content' => $content], $root);
    }

    public function createDirectory(HostingAccount $account, string $path, ?string $root = null): void
    {
        $this->assertUnderQuota($account);
        $this->run($account, 'files.create_directory', ['path' => $path], $root);
    }

    public function createFile(HostingAccount $account, string $path, ?string $root = null): void
    {
        $this->assertUnderQuota($account);
        $this->run($account, 'files.create_file', ['path' => $path], $root);
    }

    /**
     * Checagem "macia": usa o uso de disco da última verificação (ver
     * comando agendado disk-usage:refresh), não em tempo real — medir
     * de verdade a cada escrita exigiria chamar o Agent (du) a cada
     * clique, caro demais. Só bloqueia criar/escrever; ler/apagar
     * continuam liberados mesmo acima da cota, pra sempre dar pra
     * liberar espaço.
     */
    private function assertUnderQuota(HostingAccount $account): void
    {
        $plan = $account->plan;

        if ($account->disk_usage_mb !== null && $account->disk_usage_mb >= $plan->disk_quota_mb) {
            throw new RuntimeException('Cota de disco do plano atingida — remova arquivos pra liberar espaço.');
        }
    }

    public function delete(HostingAccount $account, string $path, ?string $root = null): void
    {
        $this->run($account, 'files.delete', ['path' => $path], $root);
    }

    public function rename(HostingAccount $account, string $from, string $to, ?string $root = null): void
    {
        $this->run($account, 'files.rename', ['from' => $from, 'to' => $to], $root);
    }

    /**
     * Upload não passa pelo dispatch() de ação JSON (ver
     * AgentHttpClient::uploadFile) — o conteúdo é binário, direto do
     * corpo da requisição HTTP.
     */
    public function upload(HostingAccount $account, string $path, string $content, ?string $root = null): void
    {
        $this->assertUnderQuota($account);

        if ($root !== null) {
            $this->assertRootBelongsToAccount($account, $root);
        }

        $data = $this->client->uploadFile($account->server, $account->linux_username, $path, $content, $root);

        if (($data['status'] ?? null) !== 'completed') {
            throw new RuntimeException($data['error'] ?? 'Falha ao enviar arquivo.');
        }
    }

    /** @param list<string> $paths */
    public function compress(HostingAccount $account, array $paths, string $output, ?string $root = null): void
    {
        $this->assertUnderQuota($account);
        $this->run($account, 'files.compress', ['paths' => $paths, 'output' => $output], $root);
    }

    public function extract(HostingAccount $account, string $path, string $dest, ?string $root = null): void
    {
        $this->assertUnderQuota($account);
        $this->run($account, 'files.extract', ['path' => $path, 'dest' => $dest], $root);
    }

    /**
     * Não passa pelo dispatch() de ação JSON, mesmo motivo do upload —
     * reaproveita o streamDownload() genérico (já usado pelos backups)
     * com a rota própria de arquivo do Agent. path/root vão codificados
     * num token de rota (ver FileDownloadController no Agent) em vez
     * de querystring, pra bater com a assinatura HMAC.
     */
    public function download(HostingAccount $account, string $path, ?string $root = null): StreamedResponse
    {
        if ($root !== null) {
            $this->assertRootBelongsToAccount($account, $root);
        }

        $token = rawurlencode(json_encode(['path' => $path, 'root' => $root]));
        $agentPath = "api/files/{$account->linux_username}/download/{$token}";

        return $this->client->streamDownload($account->server, $agentPath, basename($path));
    }

    private function run(HostingAccount $account, string $action, array $payload, ?string $root = null): array
    {
        if ($root !== null) {
            $this->assertRootBelongsToAccount($account, $root);
        }

        $job = $this->client->dispatch($account->server, $action, [
            'username' => $account->linux_username,
            'root' => $root,
            ...$payload,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? "Falha ao executar {$action}");
        }

        return $job->result ?? [];
    }

    private function assertRootBelongsToAccount(HostingAccount $account, string $root): void
    {
        $exists = $account->domains()
            ->where('domain', $root)
            ->where('location', 'outside_public_html')
            ->exists();

        if (! $exists) {
            throw new RuntimeException('Raiz inválida pra essa conta.');
        }
    }
}
