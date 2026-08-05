<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Http\Requests\StoreHostingAccountRequest;
use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingBackup;
use App\Domain\Hosting\Models\HostingDatabase;
use App\Domain\Hosting\Models\Plan;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Support\UsernameGenerator;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DatabaseSsoToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class HostingAccountController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', HostingAccount::class);

        $accounts = HostingAccount::with(['client', 'server', 'plan'])
            ->when($request->filled('search'), fn ($q) => $q->where('primary_domain', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.hosting-accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorize('create', HostingAccount::class);

        $clients = User::where('type', 'client')->whereDoesntHave('hostingAccounts')->orderBy('name')->get();
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

        $redirect = $redirect->with('plain_ssh_password', $account->ssh_password);

        if ($createDatabase) {
            try {
                $database = $provisioning->provisionDatabase($account, 'db1');

                return $redirect
                    ->with('status', 'Conta de hospedagem provisionada com sucesso.')
                    ->with('plain_db_name', $database->db_name)
                    ->with('plain_db_username', $database->db_username)
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

        $hosting_account->load(['client', 'server', 'plan', 'databases', 'domains', 'backups', 'cronJobs']);

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

    public function createDatabase(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-z0-9_]{1,32}$/i'],
            'username' => ['nullable', 'string', 'regex:/^[a-z0-9_]{1,32}$/i'],
            'password' => ['nullable', 'string', 'min:8', 'max:64'],
        ]);

        try {
            $database = $provisioning->provisionDatabase(
                $hosting_account,
                strtolower($data['name']),
                isset($data['username']) ? strtolower($data['username']) : null,
                $data['password'] ?? null,
            );

            return back()
                ->with('status', 'Banco de dados criado.')
                ->with('plain_db_name', $database->db_name)
                ->with('plain_db_username', $database->db_username)
                ->with('plain_db_password', $database->db_password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar banco: '.$e->getMessage());
        }
    }

    public function deleteDatabase(HostingAccount $hosting_account, HostingDatabase $database, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($database->hosting_account_id === $hosting_account->id, 404);

        $provisioning->deleteDatabase($database);

        return back()->with('status', 'Banco de dados removido.');
    }

    public function phpMyAdminSso(HostingAccount $hosting_account, HostingDatabase $database)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($database->hosting_account_id === $hosting_account->id, 404);

        $server = $hosting_account->server;
        $secret = $server->currentCredential->shared_secret;

        $token = DatabaseSsoToken::generate($database->db_username, $database->db_password, $secret);

        return redirect()->away($server->phpMyAdminBaseUrl().'/sso-login.php?token='.urlencode($token));
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


    public function updateBackupFrequency(Request $request, HostingAccount $hosting_account)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'backup_frequency' => ['required', Rule::in(config('hosting.backup_frequencies'))],
        ]);

        $hosting_account->update(['backup_frequency' => $data['backup_frequency']]);

        return back()->with('status', 'Frequência de backup atualizada.');
    }

    public function createBackup(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para gerar backup.');
        }

        try {
            $provisioning->createBackup($hosting_account);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao gerar backup: '.$e->getMessage());
        }

        return back()->with('status', 'Backup solicitado — pode levar alguns minutos. Atualize a página pra ver o resultado.');
    }

    public function downloadBackup(HostingAccount $hosting_account, HostingBackup $backup, string $filename, AgentHttpClient $client)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($backup->hosting_account_id === $hosting_account->id, 404);
        abort_unless($backup->status === 'completed', 404);
        abort_unless(collect($backup->files)->contains('filename', $filename), 404);

        $path = "api/backups/{$hosting_account->linux_username}/{$filename}";

        return $client->streamDownload($hosting_account->server, $path, $filename);
    }

    public function downloadBackupBundle(HostingAccount $hosting_account, HostingBackup $backup, string $group, AgentHttpClient $client)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($backup->hosting_account_id === $hosting_account->id, 404);
        abort_unless($backup->status === 'completed', 404);
        abort_unless(in_array($group, ['files', 'databases', 'all'], true), 404);

        $filenames = collect($backup->files)->pluck('filename')->filter(fn ($filename) => match ($group) {
            'files' => str_starts_with($filename, 'files-'),
            'databases' => str_starts_with($filename, 'db-'),
            default => true,
        })->values();

        abort_if($filenames->isEmpty(), 404);

        // Um único arquivo já comprimido (tar.gz/sql.gz) não precisa de
        // outra camada de zip por cima — só monta o pacote quando há
        // mais de um.
        if ($filenames->count() === 1) {
            $filename = $filenames->first();

            return $client->streamDownload($hosting_account->server, "api/backups/{$hosting_account->linux_username}/{$filename}", $filename);
        }

        $token = rawurlencode(json_encode(['files' => $filenames->all()]));
        $path = "api/backups/{$hosting_account->linux_username}/bundle/{$token}";
        $zipName = match ($group) {
            'files' => 'arquivos.zip',
            'databases' => 'bancos-de-dados.zip',
            default => 'backup-completo.zip',
        };

        return $client->streamDownload($hosting_account->server, $path, $zipName);
    }

    public function destroyBackup(HostingAccount $hosting_account, HostingBackup $backup, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($backup->hosting_account_id === $hosting_account->id, 404);

        try {
            $provisioning->deleteBackup($hosting_account, $backup);

            return back()->with('status', 'Backup removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover backup: '.$e->getMessage());
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
                Rule::unique('hosting_accounts', 'primary_domain'),
                Rule::notIn([$hosting_account->primary_domain]),
            ],
            'type' => ['required', 'in:addon,subdomain'],
            'location' => ['required', 'in:inside_public_html,outside_public_html'],
        ]);

        try {
            $domain = $provisioning->addDomain($hosting_account, strtolower($data['domain']), $data['type'], $data['location']);

            return back()->with($domain->status === 'active'
                ? ['status' => 'Domínio adicionado.']
                : ['error' => 'Falha ao adicionar domínio: '.$domain->last_error]);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao adicionar domínio: '.$e->getMessage());
        }
    }

    public function destroyDomain(HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($domain->hosting_account_id === $hosting_account->id, 404);

        $provisioning->removeDomain($domain);

        return back()->with('status', 'Domínio removido.');
    }

    public function updatePublicPath(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'public_path' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,63}$/i'],
        ]);

        try {
            $provisioning->updateDocumentRoot($hosting_account, $data['public_path'] ?: null);

            return back()->with('status', 'Diretório público atualizado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar diretório público: '.$e->getMessage());
        }
    }

    public function updateDomainPublicPath(Request $request, HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($domain->hosting_account_id === $hosting_account->id, 404);

        $data = $request->validate([
            'public_path' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9_-]{0,63}$/i'],
        ]);

        try {
            $provisioning->updateDomainDocumentRoot($domain, $data['public_path'] ?: null);

            return back()->with('status', 'Diretório público atualizado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar diretório público: '.$e->getMessage());
        }
    }
}
