<?php

namespace App\Domain\Servers\Http\Controllers;

use App\Domain\Servers\Models\AgentCredential;
use App\Domain\Servers\Models\AgentJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentWebhookController extends Controller
{
    /**
     * Recebe o resultado de uma ação assíncrona que o Agent processou.
     * O "correlation_id" enviado é o uuid do nosso AgentJob local,
     * gravado no momento do dispatch (ver AgentHttpClient).
     */
    public function callback(Request $request)
    {
        /** @var AgentCredential $credential */
        $credential = $request->attributes->get('agent_credential');

        $data = $request->validate([
            'job_uuid' => ['required', 'string'],
            'correlation_id' => ['nullable', 'string'],
            'action' => ['required', 'string'],
            'status' => ['required', 'string'],
            'result' => ['nullable', 'array'],
            'error' => ['nullable', 'string'],
        ]);

        $job = AgentJob::where('server_id', $credential->server_id)
            ->where('uuid', $data['correlation_id'] ?? $data['job_uuid'])
            ->first();

        if (! $job) {
            return response()->json(['ok' => false, 'message' => 'Job não encontrado.'], 404);
        }

        $job->update([
            'remote_job_id' => $data['job_uuid'],
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'error' => $data['error'] ?? null,
            'completed_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Snapshot periódico de status enviado pelo Agent (systemd timer).
     * Marca o servidor online e atualiza o instantâneo de métricas.
     */
    public function heartbeat(Request $request)
    {
        /** @var AgentCredential $credential */
        $credential = $request->attributes->get('agent_credential');

        $data = $request->validate([
            'load_avg' => ['nullable', 'numeric'],
            'disk_percent' => ['nullable', 'numeric'],
            'mem_percent' => ['nullable', 'numeric'],
        ]);

        $credential->server->update([
            'agent_status' => 'online',
            'last_heartbeat_at' => now(),
            'load_avg' => $data['load_avg'] ?? null,
            'disk_percent' => $data['disk_percent'] ?? null,
            'mem_percent' => $data['mem_percent'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}
