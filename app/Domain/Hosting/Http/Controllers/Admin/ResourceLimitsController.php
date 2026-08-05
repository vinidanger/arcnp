<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use Throwable;

class ResourceLimitsController extends Controller
{
    public function index(HostingAccount $hosting_account, AgentHttpClient $client)
    {
        $this->authorize('view', $hosting_account);

        [$usage, $usageError] = $this->fetchUsage($hosting_account, $client);

        return view('admin.hosting-accounts.resources.index', [
            'account' => $hosting_account,
            'usage' => $usage,
            'usageError' => $usageError,
        ]);
    }

    public function reapply(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        try {
            $provisioning->syncResourceLimits($hosting_account);

            return back()->with('status', 'Limites reaplicados.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao reaplicar limites: '.$e->getMessage());
        }
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function fetchUsage(HostingAccount $hosting_account, AgentHttpClient $client): array
    {
        if ($hosting_account->status !== 'active') {
            return [null, null];
        }

        $job = $client->dispatch($hosting_account->server, 'resources.usage', [
            'username' => $hosting_account->linux_username,
        ]);

        if ($job->status !== 'completed') {
            return [null, $job->error ?? 'Falha ao consultar uso atual.'];
        }

        return [$job->result, null];
    }
}
