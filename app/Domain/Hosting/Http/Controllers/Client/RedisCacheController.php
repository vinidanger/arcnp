<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Http\Controllers\Controller;
use Throwable;

class RedisCacheController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('server');

        return view('client.hosting-accounts.redis.index', ['account' => $hosting_account]);
    }

    public function regenerate(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);

        try {
            $password = $provisioning->regenerateRedisPassword($hosting_account);

            return back()->with('status', 'Credenciais Redis geradas.')->with('plain_redis_password', $password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao gerar credenciais: '.$e->getMessage());
        }
    }
}
