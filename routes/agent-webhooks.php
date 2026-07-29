<?php

use App\Domain\Servers\Http\Controllers\AgentWebhookController;
use Illuminate\Support\Facades\Route;

// Machine-to-machine: Agent -> Painel. Sem sessão/CSRF (grupo "api"),
// autenticado pela assinatura HMAC verificada em VerifySignedAgentCallback.
Route::middleware('agent.callback.signed')->prefix('agent-webhooks/{agentUuid}')->group(function () {
    Route::post('/callback', [AgentWebhookController::class, 'callback']);
    Route::post('/heartbeat', [AgentWebhookController::class, 'heartbeat']);
});
