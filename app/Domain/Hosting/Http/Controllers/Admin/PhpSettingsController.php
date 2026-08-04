<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PhpSettingsController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        return view('admin.hosting-accounts.php.index', ['account' => $hosting_account]);
    }

    public function updateVersion(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
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

    public function updateSettings(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para ajustar configurações de PHP.');
        }

        $data = $request->validate([
            'memory_limit' => ['required', 'integer', 'min:32', 'max:2048'],
            'upload_max_filesize' => ['required', 'integer', 'min:1', 'max:2048'],
            'post_max_size' => ['required', 'integer', 'min:1', 'max:2048', 'gte:upload_max_filesize'],
            'max_execution_time' => ['required', 'integer', 'min:1', 'max:300'],
            'max_input_time' => ['required', 'integer', 'min:1', 'max:300'],
            'max_input_vars' => ['required', 'integer', 'min:100', 'max:10000'],
            'max_file_uploads' => ['required', 'integer', 'min:1', 'max:100'],
            'session_gc_maxlifetime' => ['required', 'integer', 'min:60', 'max:86400'],
            'error_reporting' => ['required', Rule::in(array_keys(config('hosting.error_reporting_presets')))],
            'disable_functions' => ['nullable', 'array'],
            'disable_functions.*' => [Rule::in(config('hosting.disablable_php_functions'))],
        ]);

        $data['display_errors'] = $request->boolean('display_errors');
        $data['log_errors'] = $request->boolean('log_errors');
        $data['file_uploads'] = $request->boolean('file_uploads');
        $data['short_open_tag'] = $request->boolean('short_open_tag');
        $data['disable_functions'] = $data['disable_functions'] ?? [];

        try {
            $provisioning->updatePhpFpmSettings($hosting_account, $data);

            return back()->with('status', 'Configurações de PHP atualizadas.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar configurações de PHP: '.$e->getMessage());
        }
    }
}
