<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostedApp;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostedAppService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class HostedAppController extends Controller
{
    public function index(HostingAccount $hosting_account, HostedAppService $service)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('hostedApps', 'domains');

        $statuses = $hosting_account->hostedApps->mapWithKeys(
            fn (HostedApp $app) => [$app->id => $service->status($app)]
        );

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        return view('admin.hosting-accounts.apps.index', [
            'account' => $hosting_account,
            'domains' => $domains,
            'statuses' => $statuses,
        ]);
    }

    public function store(Request $request, HostingAccount $hosting_account, HostedAppService $service)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'runtime' => ['required', 'string', 'in:node,python'],
            'entry_file' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\/.]+$/'],
        ]);

        try {
            $service->create($hosting_account, $data['domain'], $data['runtime'], $data['entry_file']);

            return back()->with('status', 'App criado: '.$data['domain']);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar app: '.$e->getMessage());
        }
    }

    public function restart(HostingAccount $hosting_account, HostedApp $app, HostedAppService $service)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($app->hosting_account_id === $hosting_account->id, 404);

        try {
            $service->restart($app);

            return back()->with('status', 'App reiniciado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao reiniciar app: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, HostedApp $app, HostedAppService $service)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($app->hosting_account_id === $hosting_account->id, 404);

        try {
            $service->delete($app);

            return back()->with('status', 'App removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover app: '.$e->getMessage());
        }
    }
}
