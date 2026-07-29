<?php

namespace App\Domain\Servers\Http\Middleware;

use App\Domain\Servers\Models\AgentCredential;
use App\Support\RequestSigner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espelho do VerifySignedRequest do Agent, mas a chave (shared_secret)
 * é resolvida por servidor a partir do {agentUuid} na URL, já que o
 * Painel fala com N agents, cada um com seu próprio segredo.
 */
class VerifySignedAgentCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $agentUuid = $request->route('agentUuid');

        $credential = AgentCredential::where('agent_uuid', $agentUuid)
            ->whereNull('revoked_at')
            ->first();

        abort_if(! $credential, 404);

        $timestamp = $request->header('X-Agent-Timestamp');
        $nonce = $request->header('X-Agent-Nonce');
        $signature = $request->header('X-Agent-Signature');

        abort_if(blank($timestamp) || blank($nonce) || blank($signature), 401, 'Cabeçalhos de assinatura ausentes.');

        $tolerance = 30;
        abort_if(abs(time() - (int) $timestamp) > $tolerance, 401, 'Timestamp fora da janela permitida.');

        $nonceKey = "agent-callback:nonce:{$nonce}";
        abort_if(Cache::has($nonceKey), 401, 'Nonce já utilizado (replay).');

        $expected = RequestSigner::signature(
            $request->method(),
            $request->path(),
            $timestamp,
            $nonce,
            $request->getContent(),
            $credential->shared_secret,
        );

        abort_unless(hash_equals($expected, $signature), 401, 'Assinatura inválida.');

        Cache::put($nonceKey, true, $tolerance * 2);

        $request->attributes->set('agent_credential', $credential);

        return $next($request);
    }
}
