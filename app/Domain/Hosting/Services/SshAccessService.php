<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\SshKey;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

class SshAccessService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function setEnabled(HostingAccount $account, bool $enabled): void
    {
        $job = $this->client->dispatch($account->server, 'ssh.set_enabled', [
            'username' => $account->linux_username,
            'enabled' => $enabled,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao alterar acesso SSH.');
        }

        $account->update(['ssh_enabled' => $enabled]);
    }

    public function addKey(HostingAccount $account, string $name, string $publicKey): SshKey
    {
        $key = $account->sshKeys()->create(['name' => $name, 'public_key' => trim($publicKey)]);

        try {
            $this->syncKeys($account);
        } catch (RuntimeException $e) {
            $key->delete();
            throw $e;
        }

        return $key;
    }

    public function removeKey(SshKey $key): void
    {
        $account = $key->hostingAccount;
        $key->delete();

        $this->syncKeys($account);
    }

    private function syncKeys(HostingAccount $account): void
    {
        $job = $this->client->dispatch($account->server, 'ssh.sync_keys', [
            'username' => $account->linux_username,
            'keys' => $account->sshKeys()->pluck('public_key')->all(),
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar chaves SSH.');
        }
    }
}
