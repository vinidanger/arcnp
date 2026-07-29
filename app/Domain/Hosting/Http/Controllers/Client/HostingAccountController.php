<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Espelha as ações "de dono" do controller admin (SSL, banco,
 * domínios adicionais), reaproveitando o mesmo
 * HostingAccountProvisioningService — só muda quem pode acessar
 * (dono da conta, via Policy) e as views. Suspender/reativar/excluir
 * continuam só em /admin.
 */
class HostingAccountController extends Controller
{
    public function index()
    {
        $accounts = auth()->user()->hostingAccounts()->with('plan')->latest()->get();

        return view('client.hosting-accounts.index', compact('accounts'));
    }

    public function show(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['plan', 'database', 'domains']);

        return view('client.hosting-accounts.show', ['account' => $hosting_account]);
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
