<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\MailLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class MailLogController extends Controller
{
    public function index(Request $request, HostingAccount $hosting_account, MailLogService $service)
    {
        $this->authorize('view', $hosting_account);

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'lines' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ]);

        $search = $data['search'] ?? null;
        $lines = $data['lines'] ?? 200;

        $content = null;
        $error = null;

        try {
            $content = $service->tail($hosting_account, $lines, $search);
        } catch (Throwable $e) {
            $error = 'Falha ao ler o log: '.$e->getMessage();
        }

        return view('admin.hosting-accounts.mail-log.index', [
            'account' => $hosting_account,
            'search' => $search,
            'lines' => $lines,
            'content' => $content,
            'logError' => $error,
        ]);
    }
}
