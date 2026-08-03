<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Requests\V1\StoreHostingAccountRequest;
use App\Domain\Api\Http\Resources\V1\HostingAccountResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Support\UsernameGenerator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

class HostingAccountController extends Controller
{
    /**
     * Cria o cliente (se o e-mail ainda não existir) e já provisiona a
     * conta de hospedagem numa chamada só — o caso de uso real é "outro
     * painel vendeu um plano, cria tudo agora".
     */
    public function store(StoreHostingAccountRequest $request, HostingAccountProvisioningService $provisioning)
    {
        $data = $request->validated();

        $client = User::where('type', 'client')->where('email', $data['client']['email'])->first();
        $generatedPassword = null;

        if (! $client) {
            $generatedPassword = $data['client']['password'] ?? Str::password(16);

            $client = User::create([
                'name' => $data['client']['name'],
                'email' => $data['client']['email'],
                'password' => $generatedPassword,
                'type' => 'client',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $account = HostingAccount::create([
            'user_id' => $client->id,
            'server_id' => $data['server_id'],
            'plan_id' => $data['plan_id'],
            'primary_domain' => $data['primary_domain'],
            'php_version' => $data['php_version'],
            'linux_username' => UsernameGenerator::fromDomain($data['primary_domain']),
            'status' => 'creating',
        ]);

        $provisioning->provision($account);
        $account = $account->fresh(['client', 'plan', 'server']);

        return (new HostingAccountResource($account))
            ->additional(['client_password' => $generatedPassword])
            ->response()
            ->setStatusCode($account->status === 'active' ? 201 : 422);
    }

    public function show(HostingAccount $hosting_account)
    {
        return new HostingAccountResource($hosting_account->load('client', 'plan', 'server'));
    }

    public function suspend(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        try {
            $provisioning->suspend($hosting_account);

            return new HostingAccountResource($hosting_account->fresh(['client', 'plan', 'server']));
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reactivate(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        try {
            $provisioning->reactivate($hosting_account);

            return new HostingAccountResource($hosting_account->fresh(['client', 'plan', 'server']));
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        try {
            $provisioning->deprovision($hosting_account);
            $hosting_account->delete();

            return response()->json(['message' => 'Conta cancelada.']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
