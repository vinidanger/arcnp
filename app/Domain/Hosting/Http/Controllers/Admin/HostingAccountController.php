<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Http\Requests\StoreHostingAccountRequest;
use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\Plan;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Support\UsernameGenerator;
use App\Domain\Servers\Models\Server;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

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
        $createDatabase = (bool) ($data['create_database'] ?? false);
        unset($data['create_database']);

        $account = HostingAccount::create([
            ...$data,
            'linux_username' => UsernameGenerator::fromDomain($data['primary_domain']),
            'status' => 'creating',
        ]);

        $provisioning->provision($account);

        $redirect = redirect()->route('admin.hosting-accounts.show', $account);

        if ($account->status !== 'active') {
            return $redirect->with('error', 'Falha ao provisionar — veja o erro abaixo.');
        }

        if ($createDatabase) {
            try {
                $database = $provisioning->provisionDatabase($account);

                return $redirect
                    ->with('status', 'Conta de hospedagem provisionada com sucesso.')
                    ->with('plain_db_password', $database->db_password);
            } catch (Throwable $e) {
                return $redirect->with('status', 'Conta provisionada, mas o banco de dados falhou: '.$e->getMessage());
            }
        }

        return $redirect->with('status', 'Conta de hospedagem provisionada com sucesso.');
    }

    public function show(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['client', 'server', 'plan', 'database', 'domains']);

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

    public function createDatabase(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->database) {
            return back()->with('error', 'Essa conta já tem um banco de dados.');
        }

        try {
            $database = $provisioning->provisionDatabase($hosting_account);

            return back()
                ->with('status', 'Banco de dados criado.')
                ->with('plain_db_password', $database->db_password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar banco: '.$e->getMessage());
        }
    }

    public function deleteDatabase(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        $provisioning->deleteDatabase($hosting_account);

        return back()->with('status', 'Banco de dados removido.');
    }

    public function issueSsl(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para emitir SSL.');
        }

        $provisioning->issueSslCertificate($hosting_account);

        return back()->with('status', 'Emissão de certificado solicitada — deve levar alguns segundos. Atualize a página para ver o resultado.');
    }

    public function suspend(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        try {
            $provisioning->suspend($hosting_account);

            return back()->with('status', 'Conta suspensa.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao suspender: '.$e->getMessage());
        }
    }

    public function reactivate(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        try {
            $provisioning->reactivate($hosting_account);

            return back()->with('status', 'Conta reativada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao reativar: '.$e->getMessage());
        }
    }

    public function changePhpVersion(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para trocar a versão de PHP.');
        }

        $data = $request->validate([
            'php_version' => ['required', Rule::in(config('hosting.php_versions'))],
        ]);

        try {
            $provisioning->changePhpVersion($hosting_account, $data['php_version']);

            return back()->with('status', 'Versão de PHP alterada para '.$data['php_version'].'.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao trocar versão de PHP: '.$e->getMessage());
        }
    }

    public function storeDomain(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para adicionar domínios.');
        }

        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::unique('domains', 'domain'),
                Rule::notIn([$hosting_account->primary_domain]),
            ],
            'type' => ['required', 'in:addon,subdomain'],
        ]);

        $domain = $provisioning->addDomain($hosting_account, strtolower($data['domain']), $data['type']);

        return back()->with($domain->status === 'active'
            ? ['status' => 'Domínio adicionado.']
            : ['error' => 'Falha ao adicionar domínio: '.$domain->last_error]);
    }

    public function destroyDomain(HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($domain->hosting_account_id === $hosting_account->id, 404);

        $provisioning->removeDomain($domain);

        return back()->with('status', 'Domínio removido.');
    }
}
