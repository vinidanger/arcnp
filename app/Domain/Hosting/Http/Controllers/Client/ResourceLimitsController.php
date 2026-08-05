<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;

class ResourceLimitsController extends Controller
{
    public function index(HostingAccount $hosting_account, AgentHttpClient $client)
    {
        $this->authorize('view', $hosting_account);

        [$usage, $usageError] = $this->fetchUsage($hosting_account, $client);

        return view('client.hosting-accounts.resources.index', [
            'account' => $hosting_account,
            'usage' => $usage,
            'usageError' => $usageError,
        ]);
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
