<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/agent-webhooks.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Rota pública pra sistema externo (outro painel) consumir —
            // diferente do agent-webhooks.php, que é Agent -> Painel.
            // Nome de arquivo separado de propósito, pra não confundir
            // com o slot "api:" acima (já ocupado pelos webhooks).
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api-v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'type' => \App\Http\Middleware\EnsureUserType::class,
            'agent.callback.signed' => \App\Domain\Servers\Http\Middleware\VerifySignedAgentCallback::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('agent-webhooks/*') || $request->is('api/*') || $request->expectsJson());
    })->create();
