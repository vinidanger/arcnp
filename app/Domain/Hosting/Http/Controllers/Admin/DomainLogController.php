<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\DomainLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class DomainLogController extends Controller
{
    public function index(Request $request, HostingAccount $hosting_account, DomainLogService $service)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        $data = $request->validate([
            'domain' => ['nullable', 'string', 'in:'.implode(',', $domains)],
            'type' => ['nullable', 'string', 'in:access,error'],
            'lines' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ]);

        $domain = $data['domain'] ?? $hosting_account->primary_domain;
        $type = $data['type'] ?? 'access';
        $lines = $data['lines'] ?? 200;

        $content = null;
        $error = null;

        try {
            $content = $service->tail($hosting_account, $domain, $type, $lines);
        } catch (Throwable $e) {
            $error = 'Falha ao ler o log: '.$e->getMessage();
        }

        return view('admin.hosting-accounts.logs.index', [
            'account' => $hosting_account,
            'domains' => $domains,
            'domain' => $domain,
            'type' => $type,
            'lines' => $lines,
            'content' => $content,
            'logError' => $error,
        ]);
    }
}
