<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\FtpAccount;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Mesmo padrão de MailboxService: o Painel é sempre a fonte da
 * verdade, o estado é por SERVIDOR (todas as contas FTP de todas as
 * hospedagens nele, não só as dessa conta) porque o pam_userdb do
 * vsftpd é um namespace só por instância — reenvia o estado COMPLETO
 * a cada mutação. Senha não é reversível (diferente da caixa de
 * e-mail): FTP não precisa de SSO, só o hash já basta.
 */
class FtpAccountService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function usernameTaken(Server $server, string $username): bool
    {
        return FtpAccount::where('server_id', $server->id)->where('username', $username)->exists();
    }

    public function create(HostingAccount $account, string $username, string $path, string $password): FtpAccount
    {
        if ($this->usernameTaken($account->server, $username)) {
            throw new RuntimeException('Esse usuário de FTP já existe nesse servidor.');
        }

        $ftpAccount = $account->ftpAccounts()->create([
            'server_id' => $account->server_id,
            'username' => $username,
            'path' => $path,
            'password_hash' => Hash::make($password),
        ]);

        try {
            $this->syncState($account->server);
        } catch (RuntimeException $e) {
            $ftpAccount->delete();
            throw $e;
        }

        return $ftpAccount;
    }

    public function updatePassword(FtpAccount $ftpAccount, string $password): void
    {
        $original = $ftpAccount->password_hash;

        $ftpAccount->update(['password_hash' => Hash::make($password)]);

        try {
            $this->syncState($ftpAccount->server);
        } catch (RuntimeException $e) {
            $ftpAccount->update(['password_hash' => $original]);
            throw $e;
        }
    }

    public function delete(FtpAccount $ftpAccount): void
    {
        $server = $ftpAccount->server;
        $ftpAccount->delete();

        $this->syncState($server);
    }

    private function syncState(Server $server): void
    {
        $accounts = FtpAccount::where('server_id', $server->id)
            ->with('hostingAccount')
            ->get()
            ->map(fn (FtpAccount $a) => [
                'ftp_username' => $a->username,
                'linux_username' => $a->hostingAccount->linux_username,
                'path' => $a->path,
                'password_hash' => $a->password_hash,
            ])
            ->all();

        $job = $this->client->dispatch($server, 'ftp.sync_state', ['accounts' => $accounts]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar contas de FTP.');
        }
    }
}
