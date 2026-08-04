<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\SiteRedirect;
use App\Domain\Hosting\Services\SiteRedirectService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class SiteRedirectController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('siteRedirects', 'domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        return view('client.hosting-accounts.redirects.index', [
            'account' => $hosting_account,
            'domains' => $domains,
        ]);
    }

    public function store(Request $request, HostingAccount $hosting_account, SiteRedirectService $service)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'path' => ['required', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9_\-\/]*$/'],
            'destination' => ['required', 'string', 'max:2000', 'url:http,https'],
            'status_code' => ['required', 'integer', 'in:301,302'],
        ]);

        try {
            $service->create($hosting_account, $data['domain'], $data['path'], $data['destination'], $data['status_code']);

            return back()->with('status', 'Redirecionamento criado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar redirecionamento: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, SiteRedirect $redirect, SiteRedirectService $service)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($redirect->hosting_account_id === $hosting_account->id, 404);

        try {
            $service->delete($redirect);

            return back()->with('status', 'Redirecionamento removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover redirecionamento: '.$e->getMessage());
        }
    }
}
