<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\ImageOptimization;
use App\Domain\Servers\Services\AgentHttpClient;

/**
 * Otimização de imagem (WebP/AVIF, paridade com LiteSpeed) — mesmo
 * padrão de MalwareScanService::scan(): cria a linha ANTES de
 * despachar (status inicial "running"), embute o id da linha no
 * payload pra correlação no callback (AgentWebhookController).
 */
class ImageOptimizationService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function optimize(HostingAccount $account): ImageOptimization
    {
        $optimization = $account->imageOptimizations()->create(['status' => 'running']);

        try {
            $job = $this->client->dispatch($account->server, 'web.optimize_images', [
                'username' => $account->linux_username,
                'image_optimization_id' => $optimization->id,
            ]);
        } catch (\Throwable $e) {
            $optimization->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }

        // Dispatch assíncrono só falha aqui por erro de rede/conexão com
        // o Agent (não pelo resultado da otimização em si, que chega
        // depois via callback) — mesmo raciocínio do scanner de malware.
        if ($job->status === 'failed') {
            $optimization->update(['status' => 'failed', 'error' => $job->error]);
        }

        return $optimization;
    }
}
