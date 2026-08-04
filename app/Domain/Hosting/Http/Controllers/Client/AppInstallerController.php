<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\AppInstallation;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\AppInstallerService;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Throwable;

class AppInstallerController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('appInstallations', 'domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        $wordpressPhpOk = version_compare($hosting_account->php_version, config('app_catalog.wordpress.min_php_version'), '>=');

        return view('client.hosting-accounts.installer.index', [
            'account' => $hosting_account,
            'domains' => $domains,
            'catalog' => config('app_catalog'),
            'wordpressPhpOk' => $wordpressPhpOk,
        ]);
    }

    public function storeWordPress(Request $request, HostingAccount $hosting_account, AppInstallerService $installer)
    {
        $this->authorize('update', $hosting_account);

        if (version_compare($hosting_account->php_version, config('app_catalog.wordpress.min_php_version'), '<')) {
            return back()->with('error', 'A versão de PHP dessa conta é anterior à mínima exigida pelo WordPress.');
        }

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'path' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\/]*$/'],
            'site_title' => ['required', 'string', 'max:255'],
            'admin_user' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9_]+$/'],
            'admin_password' => ['required', 'string', 'min:8', 'max:100'],
            'admin_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $installer->installWordPress(
                $hosting_account,
                $data['domain'],
                $data['path'] ?? '',
                $data['site_title'],
                $data['admin_user'],
                $data['admin_password'],
                $data['admin_email'],
            );

            return back()->with('status', 'Instalação do WordPress iniciada — atualize a página em alguns instantes pra ver o resultado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao iniciar instalação: '.$e->getMessage());
        }
    }

    public function storeGenericZip(Request $request, HostingAccount $hosting_account, AppInstallerService $installer)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());
        $maxUploadMb = (int) Setting::get('max_upload_mb', (string) config('hosting.max_upload_mb'));

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'path' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\/]*$/'],
            'zip' => ['required', 'file', 'mimes:zip', 'max:'.($maxUploadMb * 1024)],
        ]);

        try {
            $installer->installGenericZip(
                $hosting_account,
                $data['domain'],
                $data['path'] ?? '',
                file_get_contents($request->file('zip')->getRealPath()),
            );

            return back()->with('status', 'App extraído com sucesso.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao instalar app: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, AppInstallation $installation, AppInstallerService $installer)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($installation->hosting_account_id === $hosting_account->id, 404);

        try {
            $installer->delete($installation);

            return back()->with('status', 'App removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover app: '.$e->getMessage());
        }
    }
}
