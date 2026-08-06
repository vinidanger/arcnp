<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingBackup;
use App\Domain\Hosting\Models\HostingDatabase;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use App\Support\DatabaseSsoToken;
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
    /**
     * Cliente sempre tem no máximo uma hospedagem — "a lista" não faz
     * mais sentido, redireciona direto pra ela (mesmo espírito do
     * DashboardController).
     */
    public function index()
    {
        $account = auth()->user()->hostingAccount;

        return $account
            ? redirect()->route('client.hosting-accounts.show', $account)
            : redirect()->route('client.dashboard');
    }

    public function show(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['plan', 'databases', 'domains', 'backups', 'cronJobs']);

        return view('client.hosting-accounts.show', ['account' => $hosting_account]);
    }

    /**
     * Página própria (template cPanel) pro que hoje é uma aba dentro de
     * show() no template Padrão — mesmo conteúdo, via partial
     * compartilhada (ver resources/views/client/hosting-accounts/partials).
     */
    public function domainsIndex(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['domains']);

        return view('client.hosting-accounts.domains.index', ['account' => $hosting_account]);
    }

    public function databasesIndex(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['databases']);

        return view('client.hosting-accounts.databases.index', ['account' => $hosting_account]);
    }

    public function backupsIndex(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load(['plan', 'backups']);

        return view('client.hosting-accounts.backups.index', ['account' => $hosting_account]);
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

        // Aceita "/public", "public/" etc. — tira barra e espaço nas
        // pontas antes de validar, em vez de rejeitar por causa disso.
        $request->merge(['public_path' => trim((string) $request->input('public_path', ''), " /\t\n\r\0\x0B")]);

        $data = $request->validate([
            'public_path' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9_-]*(\/[a-z0-9][a-z0-9_-]*)*$/i'],
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

        $request->merge(['public_path' => trim((string) $request->input('public_path', ''), " /\t\n\r\0\x0B")]);

        $data = $request->validate([
            'public_path' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9_-]*(\/[a-z0-9][a-z0-9_-]*)*$/i'],
        ]);

        try {
            $provisioning->updateDomainDocumentRoot($domain, $data['public_path'] ?: null);

            return back()->with('status', 'Diretório público atualizado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar diretório público: '.$e->getMessage());
        }
    }
}
