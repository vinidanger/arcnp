<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\SshKey;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Str;
use RuntimeException;

class SshAccessService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    /**
     * Ao liberar pela primeira vez (sem senha ainda definida), já gera
     * uma — devolve o valor em texto puro pra mostrar UMA vez só (mesmo
     * padrão do banco de dados/MySQL). Reativar depois de já ter senha
     * não gera outra sozinho; pra isso existe regeneratePassword().
     */
    public function setEnabled(HostingAccount $account, bool $enabled): ?string
    {
        $job = $this->client->dispatch($account->server, 'ssh.set_enabled', [
            'username' => $account->linux_username,
            'enabled' => $enabled,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao alterar acesso SSH.');
        }

        $account->update(['ssh_enabled' => $enabled]);

        if ($enabled && ! $account->ssh_password) {
            return $this->regeneratePassword($account);
        }

        return null;
    }

    /**
     * Usado tanto pelo login (LoginRequest) quanto pela regra de
     * "senha atual" (CurrentPasswordOrSsh) — ssh_password é `encrypted`
     * (reversível, precisa continuar em texto puro pro Agent), não
     * `hashed`, então a comparação é feita direto, não via Hash::check().
     */
    public static function verifyPassword(HostingAccount $account, string $password): bool
    {
        if (! $account->ssh_password) {
            return false;
        }

        return hash_equals((string) $account->ssh_password, $password);
    }

    public function regeneratePassword(HostingAccount $account): string
    {
        $password = Str::password(20);

        $this->setPassword($account, $password);

        return $password;
    }

    /**
     * Deixa admin/cliente escolher a própria senha em vez de aceitar só
     * a gerada automaticamente.
     */
    public function setPassword(HostingAccount $account, string $password): void
    {
        $job = $this->client->dispatch($account->server, 'ssh.set_password', [
            'username' => $account->linux_username,
            'password' => $password,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao definir senha SSH.');
        }

        $account->update(['ssh_password' => $password]);
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
