<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->forgetIntendedIfWrongArea($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * "intended" pode ter sido gravado por uma visita de CONVIDADO a uma
     * URL da área errada (ex.: alguém sem sessão tentou /admin/... e
     * quem efetivamente loga em seguida no mesmo navegador é cliente) —
     * sem essa checagem, o redirect levaria a uma rota que esse usuário
     * não tem permissão de acessar (403 via middleware "type"), em vez
     * do dashboard correto pro seu tipo.
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

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
