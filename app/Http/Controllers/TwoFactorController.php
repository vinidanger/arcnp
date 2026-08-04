<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    /**
     * Gera um segredo novo (ainda NÃO confirmado — two_factor_confirmed_at
     * continua null até o usuário provar que configurou certo no app
     * autenticador, evita a pessoa se trancar fora por engano). A chave
     * fica exposta uma vez só na sessão pro formulário de confirmação
     * mostrar o texto (sem QR code — ver TwoFactorAuthenticationService,
     * decisão de escopo documentada no plano).
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'A autenticação de dois fatores já está ativada.');
        }

        $secret = app(TwoFactorAuthenticationService::class)->generateSecretKey();

        $user->update(['two_factor_secret' => $secret]);

        return back()->with('two_factor_setup_key', $secret);
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        if (! $user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Nada pendente de confirmação.');
        }

        $data = $request->validate(['code' => ['required', 'string']]);

        if (! $twoFactor->verify($user->two_factor_secret, $data['code'])) {
            return back()->with('error', 'Código inválido.');
        }

        $codes = $twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ]);

        return back()->with('two_factor_recovery_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return back()->with('status', 'Autenticação de dois fatores desativada.');
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Ative a autenticação de dois fatores primeiro.');
        }

        $codes = $twoFactor->generateRecoveryCodes();

        $user->update(['two_factor_recovery_codes' => $codes]);

        return back()->with('two_factor_recovery_codes', $codes);
    }
}
