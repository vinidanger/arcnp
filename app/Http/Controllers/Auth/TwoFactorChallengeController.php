<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Etapa entre "senha certa" e "sessão de verdade" quando a conta tem
 * 2FA ativado — ver AuthenticatedSessionController::store(), que
 * desloga de novo e guarda o id pendente na sessão em vez de logar
 * direto. Sem isso, a senha sozinha já bastaria mesmo com 2FA ligado.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $throttleKey = 'two-factor:'.$userId.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['code' => "Muitas tentativas. Tente de novo em {$seconds} segundos."]);
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.2fa_user_id', 'login.remember']);

            return redirect()->route('login');
        }

        $verified = false;

        if (! empty($data['code']) && $twoFactor->verify($user->two_factor_secret, $data['code'])) {
            $verified = true;
        } elseif (! empty($data['recovery_code'])) {
            $verified = $this->consumeRecoveryCode($user, $data['recovery_code']);
        }

        if (! $verified) {
            RateLimiter::hit($throttleKey);

            return back()->withErrors(['code' => 'Código inválido.']);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.2fa_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->forgetIntendedIfWrongArea($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));
        $index = array_search($normalized, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->update(['two_factor_recovery_codes' => array_values($codes)]);

        return true;
    }

    /**
     * Mesma lógica de AuthenticatedSessionController::forgetIntendedIfWrongArea()
     * — duplicada de propósito (é pequena, e as duas classes não têm
     * outra relação que justifique uma dependência entre elas só por
     * causa disso).
     */
    private function forgetIntendedIfWrongArea(Request $request): void
    {
        $intendedUrl = $request->session()->get('url.intended');

        if (! $intendedUrl) {
            return;
        }

        $path = (string) parse_url($intendedUrl, PHP_URL_PATH);
        $expectedPrefix = $request->user()->isAdmin() ? '/admin' : '/cliente';

        if (! str_starts_with($path, $expectedPrefix)) {
            $request->session()->forget('url.intended');
        }
    }
}
