<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Requests\V1\StoreHostingAccountRequest;
use App\Domain\Api\Http\Resources\V1\HostingAccountResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Support\UsernameGenerator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class HostingAccountController extends Controller
{
    /**
     * Listagem paginada — não existia antes (a API só permitia consultar
     * por ID já conhecido). Filtros simples pra um painel externo montar
     * a própria listagem sem precisar espelhar o banco inteiro.
     */
    public function index(Request $request)
    {
        $query = HostingAccount::query()->with(['client', 'plan', 'server']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->integer('server_id'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($request->filled('search')) {
            $query->where('primary_domain', 'like', '%'.$request->string('search').'%');
        }

        $perPage = min((int) $request->integer('per_page', 15), 100);

        return HostingAccountResource::collection($query->latest()->paginate($perPage));
    }

    /**
     * Cria um cliente novo e já provisiona a conta de hospedagem numa
     * chamada só — o caso de uso real é "outro painel vendeu um plano,
     * cria tudo agora". Sempre cria um cliente novo (nunca reaproveita
     * por e-mail — isso aqui é só contato/referência, não identifica
     * cliente; cada chamada é uma hospedagem nova, 1 cliente = 1 conta).
     */
    public function store(StoreHostingAccountRequest $request, HostingAccountProvisioningService $provisioning)
    {
        $data = $request->validated();

        $client = User::create([
            'name' => $data['client']['name'],
            'email' => $data['client']['email'] ?? null,
            // users.password não é credencial de login pro cliente (isso
            // é ssh_password, gerado no provisionamento abaixo).
            'password' => Str::password(32),
            'type' => 'client',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

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
            ->additional([
                'client_username' => $account->linux_username,
                'client_password' => $account->ssh_password,
            ])
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
