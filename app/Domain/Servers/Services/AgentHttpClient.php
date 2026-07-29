<?php

namespace App\Domain\Servers\Services;

use App\Domain\Servers\Models\AgentJob;
use App\Domain\Servers\Models\Server;
use App\Support\RequestSigner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Único ponto do Painel que fala com o Agent. Assina cada requisição
 * (HMAC + timestamp + nonce, mesmo esquema do Agent) e registra um
 * AgentJob local para toda ação disparada — é o log central de
 * auditoria e o que a Fase 4 usa para saber se algo já foi enviado.
 */
class AgentHttpClient
{
    public function dispatch(Server $server, string $action, array $payload = []): AgentJob
    {
        $credential = $server->currentCredential;

        abort_if(! $credential, 422, 'Servidor sem credencial de pareamento ativa.');

        $job = $server->agentJobs()->create([
            'action' => $action,
            'payload' => $payload,
            'status' => 'queued',
        ]);

        $path = 'api/commands';
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $body = json_encode([
            'action' => $action,
            'payload' => $payload,
            'correlation_id' => $job->uuid,
        ]);

        $signature = RequestSigner::signature('POST', $path, $timestamp, $nonce, $body, $credential->shared_secret);

        $job->update(['status' => 'sent', 'dispatched_at' => now()]);

        try {
            $response = Http::withHeaders([
                'X-Agent-Timestamp' => $timestamp,
                'X-Agent-Nonce' => $nonce,
                'X-Agent-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                // O Agent sempre usa certificado self-signed por padrão (deploy/README.md
                // do arcnp-agent) — autenticidade e integridade vêm da assinatura HMAC,
                // não da cadeia de confiança TLS. Validar contra uma CA aqui nunca bateria.
                ->withOptions(['verify' => false])
                ->withBody($body, 'application/json')
                ->post("{$server->agentBaseUrl()}/{$path}");
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'error' => 'Falha de conexão com o Agent: '.$e->getMessage(),
                'completed_at' => now(),
            ]);

            return $job;
        }

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            $job->update([
                'status' => 'failed',
                'remote_job_id' => $data['job_id'] ?? null,
                'error' => $data['message'] ?? "Agent respondeu HTTP {$response->status()}",
                'completed_at' => now(),
            ]);

            return $job;
        }

        // Síncrono: o Agent já devolve o resultado final na mesma resposta.
        // Assíncrono (202): fica "running" até o callback assinado atualizar o status.
        $job->update([
            'remote_job_id' => $data['job_id'] ?? null,
            'status' => $response->status() === 202 ? 'running' : ($data['status'] ?? 'completed'),
            'result' => $data['result'] ?? null,
            'error' => $data['error'] ?? null,
            'completed_at' => $response->status() === 202 ? null : now(),
        ]);

        return $job->fresh();
    }
}
