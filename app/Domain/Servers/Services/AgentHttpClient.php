<?php

namespace App\Domain\Servers\Services;

use App\Domain\Servers\Models\AgentJob;
use App\Domain\Servers\Models\Server;
use App\Support\RequestSigner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Download não passa pelo /api/commands (esse endpoint devolve
     * JSON, não é feito pra binário) — é uma rota própria no Agent
     * (ver routes/api.php dele), assinada com o mesmo esquema HMAC.
     * Faz streaming nos dois sentidos (Agent -> Painel -> navegador)
     * pra não bufferizar o arquivo inteiro na memória do Painel.
     */
    public function streamDownload(Server $server, string $path, string $filename): StreamedResponse
    {
        $credential = $server->currentCredential;

        abort_if(! $credential, 422, 'Servidor sem credencial de pareamento ativa.');

        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $signature = RequestSigner::signature('GET', $path, $timestamp, $nonce, '', $credential->shared_secret);

        $response = Http::withHeaders([
            'X-Agent-Timestamp' => $timestamp,
            'X-Agent-Nonce' => $nonce,
            'X-Agent-Signature' => $signature,
        ])
            ->timeout(300)
            ->withOptions(['verify' => false, 'stream' => true])
            ->get("{$server->agentBaseUrl()}/{$path}");

        abort_unless($response->successful(), 502, 'Falha ao baixar o backup do Agent.');

        return new StreamedResponse(function () use ($response) {
            $body = $response->toPsrResponse()->getBody();

            while (! $body->eof()) {
                echo $body->read(8192);
                flush();
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Mesmo motivo do streamDownload: conteúdo binário arbitrário não
     * cabe bem em um campo string de JSON (custo de base64 + risco de
     * bytes inválidos) — vai pro corpo bruto de uma rota própria no
     * Agent (ver routes/api.php dele), assinada com o mesmo esquema
     * HMAC. Caminho/raiz vão em cabeçalho (com urlencode, por segurança
     * contra caractere fora do padrão HTTP em nome de arquivo).
     */
    public function uploadFile(Server $server, string $username, string $relativePath, string $content, ?string $root = null): array
    {
        $credential = $server->currentCredential;

        abort_if(! $credential, 422, 'Servidor sem credencial de pareamento ativa.');

        $path = "api/files/{$username}/upload";
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $signature = RequestSigner::signature('POST', $path, $timestamp, $nonce, $content, $credential->shared_secret);

        try {
            $response = Http::withHeaders([
                'X-Agent-Timestamp' => $timestamp,
                'X-Agent-Nonce' => $nonce,
                'X-Agent-Signature' => $signature,
                'X-Upload-Path' => rawurlencode($relativePath),
                'X-Upload-Root' => $root !== null ? rawurlencode($root) : '',
            ])
                ->timeout(120)
                ->withOptions(['verify' => false])
                ->withBody($content, 'application/octet-stream')
                ->post("{$server->agentBaseUrl()}/{$path}");
        } catch (\Throwable $e) {
            throw new RuntimeException('Falha de conexão com o Agent: '.$e->getMessage());
        }

        $data = $response->json() ?? [];

        if (! $response->successful() || ($data['status'] ?? null) !== 'completed') {
            throw new RuntimeException($data['error'] ?? $data['message'] ?? "Agent respondeu HTTP {$response->status()}");
        }

        return $data;
    }
}
