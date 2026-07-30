<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\DnsZone;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\MailDomain;
use App\Domain\Hosting\Models\Mailbox;
use App\Domain\Hosting\Services\DnsZoneService;
use App\Domain\Hosting\Services\MailboxService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class MailDomainController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('mailDomains.mailboxes', 'server');

        $activeDomains = $hosting_account->mailDomains->pluck('domain')->all();
        $candidates = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());
        $availableDomains = array_values(array_diff(array_unique($candidates), $activeDomains));

        return view('admin.hosting-accounts.mail.index', [
            'account' => $hosting_account,
            'availableDomains' => $availableDomains,
        ]);
    }

    public function store(Request $request, HostingAccount $hosting_account, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate(['domain' => ['required', 'string', 'max:255']]);

        try {
            $mail->enableDomain($hosting_account, $data['domain']);

            return back()->with('status', 'E-mail ativado pra esse domínio.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao ativar e-mail: '.$e->getMessage());
        }
    }

    public function show(HostingAccount $hosting_account, MailDomain $mail_domain)
    {
        $this->authorize('view', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);

        $mail_domain->load('mailboxes');

        $dnsZone = DnsZone::where('domain', $mail_domain->domain)->first();

        return view('admin.hosting-accounts.mail.show', [
            'account' => $hosting_account,
            'mailDomain' => $mail_domain,
            'hasDnsZone' => $dnsZone !== null,
        ]);
    }

    public function destroy(HostingAccount $hosting_account, MailDomain $mail_domain, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);

        try {
            $mail->disableDomain($mail_domain);

            return redirect()
                ->route('admin.hosting-accounts.mail.index', $hosting_account)
                ->with('status', 'E-mail desativado pra esse domínio.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao desativar e-mail: '.$e->getMessage());
        }
    }

    public function createDnsRecords(HostingAccount $hosting_account, MailDomain $mail_domain, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);

        $zone = DnsZone::where('domain', $mail_domain->domain)->first();

        if (! $zone) {
            return back()->with('error', 'Esse domínio não tem zona DNS gerenciada pelo Painel — cadastre os registros manualmente onde o DNS dele estiver hospedado.');
        }

        try {
            $dns->addRecord($zone, ['type' => 'TXT', 'name' => '@', 'content' => $mail_domain->spfRecordValue(), 'ttl' => 3600, 'priority' => null]);

            if ($mail_domain->dkim_txt_value) {
                $dns->addRecord($zone, ['type' => 'TXT', 'name' => $mail_domain->dkimSelector().'._domainkey', 'content' => $mail_domain->dkim_txt_value, 'ttl' => 3600, 'priority' => null]);
            }

            $dns->addRecord($zone, ['type' => 'TXT', 'name' => '_dmarc', 'content' => $mail_domain->dmarcRecordValue(), 'ttl' => 3600, 'priority' => null]);

            return back()->with('status', 'Registros SPF/DKIM/DMARC criados na zona DNS.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar registros: '.$e->getMessage());
        }
    }

    public function storeMailbox(Request $request, HostingAccount $hosting_account, MailDomain $mail_domain, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);

        $data = $request->validate([
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9._-]*$/i'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        $password = $data['password'] ?: Str::password(16);

        try {
            $mailbox = $mail->createMailbox($mail_domain, $data['local_part'], $password);

            return back()->with('status', 'Caixa de e-mail criada: '.$mailbox->email())->with('plain_mailbox_password', $password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar caixa: '.$e->getMessage());
        }
    }

    public function updateMailboxPassword(Request $request, HostingAccount $hosting_account, MailDomain $mail_domain, Mailbox $mailbox, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);
        abort_unless($mailbox->mail_domain_id === $mail_domain->id, 404);

        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:100', 'confirmed']]);

        try {
            $mail->updateMailboxPassword($mailbox, $data['password']);

            return back()->with('status', 'Senha atualizada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar senha: '.$e->getMessage());
        }
    }

    public function destroyMailbox(HostingAccount $hosting_account, MailDomain $mail_domain, Mailbox $mailbox, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);
        abort_unless($mailbox->mail_domain_id === $mail_domain->id, 404);

        try {
            $mail->deleteMailbox($mailbox);

            return back()->with('status', 'Caixa removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover caixa: '.$e->getMessage());
        }
    }

    public function webmailSso(HostingAccount $hosting_account, MailDomain $mail_domain, Mailbox $mailbox, MailboxService $mail)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mail_domain->hosting_account_id === $hosting_account->id, 404);
        abort_unless($mailbox->mail_domain_id === $mail_domain->id, 404);

        return redirect()->away($mail->webmailSsoUrl($mailbox));
    }
}
