<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\MailDomain;
use App\Domain\Hosting\Models\Mailbox;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Support\MailboxSsoToken;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * O Painel é sempre a fonte da verdade, e o estado do Postfix/Dovecot é
 * por SERVIDOR (todo domínio/caixa ativo nele, não só os da conta) —
 * mesma lógica do DnsZoneService. A senha da caixa fica guardada
 * reversível (Mailbox::password, cast "encrypted") porque o SSO do
 * webmail e a exibição pro cliente precisam do valor em texto puro; o
 * Agent nunca recebe esse texto puro — a cada sync recalculamos um
 * hash novo (Hash::make, bcrypt) só pra transmitir.
 */
class MailboxService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function enableDomain(HostingAccount $account, string $domain): MailDomain
    {
        if ($domain !== $account->primary_domain && ! $account->domains()->where('domain', $domain)->exists()) {
            throw new RuntimeException('Esse domínio não pertence a essa conta.');
        }

        if (MailDomain::where('domain', $domain)->exists()) {
            throw new RuntimeException('Esse domínio já tem e-mail ativado.');
        }

        $mailDomain = $account->mailDomains()->create(['domain' => $domain]);

        try {
            $this->syncState($account->server);
            $this->syncDkim($account->server);
        } catch (RuntimeException $e) {
            $mailDomain->delete();
            throw $e;
        }

        return $mailDomain->fresh();
    }

    public function disableDomain(MailDomain $mailDomain): void
    {
        if ($mailDomain->mailboxes()->exists()) {
            throw new RuntimeException('Apague as caixas de e-mail desse domínio antes de desativar.');
        }

        $server = $mailDomain->hostingAccount->server;
        $domain = $mailDomain->domain;

        $remaining = MailDomain::whereHas('hostingAccount', fn ($q) => $q->where('server_id', $server->id))
            ->whereKeyNot($mailDomain->id)
            ->pluck('domain')
            ->all();

        $job = $this->client->dispatch($server, 'mail.delete_dkim', [
            'domain' => $domain,
            'remaining_domains' => $remaining,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao remover DKIM.');
        }

        $mailDomain->delete();

        $this->syncState($server);
    }

    public function createMailbox(MailDomain $mailDomain, string $localPart, string $password): Mailbox
    {
        $account = $mailDomain->hostingAccount;

        if ($account->mailboxes()->count() >= $account->plan->max_email_accounts) {
            throw new RuntimeException('Limite de contas de e-mail do plano atingido.');
        }

        $mailbox = $mailDomain->mailboxes()->create(['local_part' => $localPart, 'password' => $password]);

        try {
            $this->syncState($account->server);
        } catch (RuntimeException $e) {
            $mailbox->delete();
            throw $e;
        }

        return $mailbox;
    }

    public function updateMailboxPassword(Mailbox $mailbox, string $password): void
    {
        $original = $mailbox->password;

        $mailbox->update(['password' => $password]);

        try {
            $this->syncState($mailbox->mailDomain->hostingAccount->server);
        } catch (RuntimeException $e) {
            $mailbox->update(['password' => $original]);
            throw $e;
        }
    }

    public function deleteMailbox(Mailbox $mailbox): void
    {
        $server = $mailbox->mailDomain->hostingAccount->server;
        $mailbox->delete();

        $this->syncState($server);
    }

    public function webmailSsoUrl(Mailbox $mailbox): string
    {
        $server = $mailbox->mailDomain->hostingAccount->server;
        $secret = $server->currentCredential->shared_secret;

        $token = MailboxSsoToken::generate($mailbox->email(), $mailbox->password, $secret);

        return $server->webmailBaseUrl().'/sso-login.php?token='.urlencode($token);
    }

    private function syncState(Server $server): void
    {
        $domains = MailDomain::whereHas('hostingAccount', fn ($q) => $q->where('server_id', $server->id))
            ->pluck('domain')
            ->all();

        $mailboxes = Mailbox::whereHas('mailDomain.hostingAccount', fn ($q) => $q->where('server_id', $server->id))
            ->with('mailDomain.hostingAccount')
            ->get()
            ->map(fn (Mailbox $mailbox) => [
                'username' => $mailbox->mailDomain->hostingAccount->linux_username,
                'domain' => $mailbox->mailDomain->domain,
                'local_part' => $mailbox->local_part,
                'password_hash' => Hash::make($mailbox->password),
            ])
            ->all();

        $job = $this->client->dispatch($server, 'mail.sync_state', [
            'domains' => $domains,
            'mailboxes' => $mailboxes,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar e-mail.');
        }
    }

    private function syncDkim(Server $server): void
    {
        $domains = MailDomain::whereHas('hostingAccount', fn ($q) => $q->where('server_id', $server->id))
            ->pluck('domain')
            ->all();

        $job = $this->client->dispatch($server, 'mail.sync_dkim', ['domains' => $domains]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar DKIM.');
        }

        foreach (($job->result['records'] ?? []) as $domain => $txtValue) {
            MailDomain::where('domain', $domain)->update(['dkim_txt_value' => $txtValue]);
        }
    }
}
