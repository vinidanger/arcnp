<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\MailLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

/**
 * O log do Postfix é do SERVIDOR inteiro, não por conta — diferente do
 * admin, o cliente NUNCA pode buscar texto livre nele (vazaria
 * metadado de e-mail de outras contas no mesmo servidor). Só pode
 * escolher uma das próprias caixas cadastradas, via <select> — nunca
 * um campo de texto solto.
 */
class MailLogController extends Controller
{
    public function index(Request $request, HostingAccount $hosting_account, MailLogService $service)
    {
        $this->authorize('view', $hosting_account);

        $mailboxes = $hosting_account->mailboxes()->with('mailDomain')->get()->map(fn ($m) => $m->email())->values()->all();

        $data = $request->validate([
            'mailbox' => ['nullable', 'string', 'in:'.implode(',', $mailboxes ?: ['__none__'])],
            'lines' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ]);

        $mailbox = $data['mailbox'] ?? null;
        $lines = $data['lines'] ?? 200;

        $content = null;
        $error = null;

        if ($mailbox) {
            try {
                $content = $service->tail($hosting_account, $lines, $mailbox);
            } catch (Throwable $e) {
                $error = 'Falha ao ler o log: '.$e->getMessage();
            }
        }

        return view('client.hosting-accounts.mail-log.index', [
            'account' => $hosting_account,
            'mailboxes' => $mailboxes,
            'mailbox' => $mailbox,
            'lines' => $lines,
            'content' => $content,
            'logError' => $error,
        ]);
    }
}
