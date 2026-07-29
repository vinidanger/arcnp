<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Http\Requests\StoreHostingAccountRequest;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\Plan;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Support\UsernameGenerator;
use App\Domain\Servers\Models\Server;
use App\Http\Controllers\Controller;
use App\Models\User;

class HostingAccountController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', HostingAccount::class);

        $accounts = HostingAccount::with(['client', 'server', 'plan'])->latest()->paginate(20);

        return view('admin.hosting-accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorize('create', HostingAccount::class);

        $clients = User::where('type', 'client')->orderBy('name')->get();
        $servers = Server::orderBy('name')->get();
        $plans = Plan::where('is_active', true)->orderBy('name')->get();

        return view('admin.hosting-accounts.create', compact('clients', 'servers', 'plans'));
    }

    public function store(StoreHostingAccountRequest $request, HostingAccountProvisioningService $provisioning)
    {
        $data = $request->validated();

        $account = HostingAccount::create([
            ...$data,
            'linux_username' => UsernameGenerator::fromDomain($data['primary_domain']),
            'status' => 'creating',
        ]);

        $provisioning->provision($account);

        return redirect()
            ->route('admin.hosting-accounts.show', $account)
            ->with('status', $account->status === 'active'
                ? 'Conta de hospedagem provisionada com sucesso.'
                : 'Falha ao provisionar — veja o erro abaixo.');
    }

    public function show(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['client', 'server', 'plan']);

        return view('admin.hosting-accounts.show', ['account' => $hosting_account]);
    }

    public function retry(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('view', $hosting_account);

        $provisioning->provision($hosting_account);

        return back()->with('status', $hosting_account->fresh()->status === 'active'
            ? 'Provisionado com sucesso.'
            : 'Falha ao provisionar novamente — veja o erro abaixo.');
    }

    public function destroy(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('delete', $hosting_account);

        $provisioning->deprovision($hosting_account);
        $hosting_account->delete();

        return redirect()->route('admin.hosting-accounts.index')->with('status', 'Conta de hospedagem removida.');
    }
}
