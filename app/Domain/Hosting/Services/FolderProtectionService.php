<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\FolderProtection;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Diferente do mail/DNS, o Agent NÃO regenera o vhost inteiro aqui —
 * só reescreve o trecho entre marcadores dentro do arquivo já
 * existente (ver SyncFolderProtectionsAction). Ainda assim, o Painel
 * reenvia o estado COMPLETO das proteções daquele domínio a cada
 * mutação, mesmo padrão do resto — o Agent decide o que sobra/some.
 */
class FolderProtectionService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function create(HostingAccount $account, string $domain, string $path, string $username, string $password): FolderProtection
    {
        $protection = $account->folderProtections()->create([
            'domain' => $domain,
            'path' => $path,
            'username' => $username,
            'password_hash' => Hash::make($password),
        ]);

        try {
            $this->syncState($account, $domain);
        } catch (RuntimeException $e) {
            $protection->delete();
            throw $e;
        }

        return $protection;
    }

    public function delete(FolderProtection $protection): void
    {
        $account = $protection->hostingAccount;
        $domain = $protection->domain;
        $protection->delete();

        $this->syncState($account, $domain);
    }

    /**
     * Chamado depois de qualquer ação que regenere o vhost inteiro a
     * partir do stub (troca de versão de PHP, emissão de SSL) — sem
     * isso, o bloco de proteção seria perdido silenciosamente na
     * próxima vez que uma dessas ações reescrever o arquivo. Só
     * dispara se o domínio realmente tiver proteção cadastrada, pra
     * não gerar uma chamada ao Agent à toa pra maioria das contas.
     */
    public function resyncIfNeeded(HostingAccount $account, string $domain): void
    {
        if ($account->folderProtections()->where('domain', $domain)->exists()) {
            $this->syncState($account, $domain);
        }
    }

    private function syncState(HostingAccount $account, string $domain): void
    {
        $protections = $account->folderProtections()
            ->where('domain', $domain)
            ->get()
            ->map(fn (FolderProtection $p) => [
                'id' => $p->id,
                'path' => $p->path,
                'htpasswd_username' => $p->username,
                'password_hash' => $p->password_hash,
            ])
            ->all();

        $job = $this->client->dispatch($account->server, 'web.sync_folder_protections', [
            'domain' => $domain,
            'protections' => $protections,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar proteção de pasta.');
        }
    }
}
