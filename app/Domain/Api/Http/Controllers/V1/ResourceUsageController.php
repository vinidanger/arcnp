<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;

class ResourceUsageController extends Controller
{
    /**
     * Único endpoint da API que não lê tabela nenhuma — despacha
     * síncrono pro Agent (mesmo padrão de
     * ResourceLimitsController::fetchUsage(), usado pelas telas
     * admin/cliente).
     */
    public function index(HostingAccount $hosting_account, AgentHttpClient $client)
    {
        if ($hosting_account->status !== 'active') {
            return response()->json(['message' => 'Conta não está ativa.'], 422);
        }

        $job = $client->dispatch($hosting_account->server, 'resources.usage', [
            'username' => $hosting_account->linux_username,
        ]);

        if ($job->status !== 'completed') {
            return response()->json(['message' => $job->error ?? 'Falha ao consultar uso atual.'], 422);
        }

        return response()->json(['data' => $job->result]);
    }
}
