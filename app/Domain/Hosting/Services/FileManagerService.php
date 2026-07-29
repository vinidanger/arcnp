<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Todas as operações são síncronas — o Agent já responde na mesma
 * requisição (nada assíncrono aqui, ao contrário de SSL/backup).
 * Restrito a public_html (ver FileManagerPath no Agent); nada aqui
 * decide isso, só repassa o path — a fronteira é sempre imposta do
 * lado do Agent.
 */
class FileManagerService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function list(HostingAccount $account, string $path): array
    {
        return $this->run($account, 'files.list', ['path' => $path]);
    }

    public function read(HostingAccount $account, string $path): array
    {
        return $this->run($account, 'files.read', ['path' => $path]);
    }

    public function write(HostingAccount $account, string $path, string $content): void
    {
        $this->run($account, 'files.write', ['path' => $path, 'content' => $content]);
    }

    public function createDirectory(HostingAccount $account, string $path): void
    {
        $this->run($account, 'files.create_directory', ['path' => $path]);
    }

    public function createFile(HostingAccount $account, string $path): void
    {
        $this->run($account, 'files.create_file', ['path' => $path]);
    }

    public function delete(HostingAccount $account, string $path): void
    {
        $this->run($account, 'files.delete', ['path' => $path]);
    }

    public function rename(HostingAccount $account, string $from, string $to): void
    {
        $this->run($account, 'files.rename', ['from' => $from, 'to' => $to]);
    }

    private function run(HostingAccount $account, string $action, array $payload): array
    {
        $job = $this->client->dispatch($account->server, $action, [
            'username' => $account->linux_username,
            ...$payload,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? "Falha ao executar {$action}");
        }

        return $job->result ?? [];
    }
}
