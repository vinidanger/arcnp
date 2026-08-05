<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Cliente sempre tem no máximo uma hospedagem — o "dashboard" É a
     * hospedagem dele, sem lista/seletor no meio (estilo cPanel/
     * DirectAdmin). Se ele ainda não tem conta (janela entre o admin
     * criar o usuário e a hospedagem ser provisionada), mostra uma
     * página de espera em vez de redirecionar pra lugar nenhum.
     */
    public function __invoke()
    {
        $account = Auth::user()->hostingAccount;

        if (! $account) {
            return view('client.dashboard-empty');
        }

        return redirect()->route('client.hosting-accounts.show', $account);
    }
}
