<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Hosting\Services\SshAccessService;
use App\Http\Controllers\Controller;
use App\Rules\CurrentPasswordOrSsh;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * Pra cliente, essa É a senha de SSH agora (login por usuário da
     * hospedagem usa a mesma credencial) — grava em ssh_password via
     * SshAccessService em vez de users.password. Admin continua exatamente
     * como o Breeze padrão.
     */
    public function update(Request $request, SshAccessService $ssh): RedirectResponse
    {
        $user = $request->user();

        if ($user->isClient()) {
            $account = $user->hostingAccount;

            if (! $account) {
                return back()->withErrors(['password' => 'Sua hospedagem ainda não foi provisionada.'], 'updatePassword');
            }

            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', new CurrentPasswordOrSsh],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            try {
                $ssh->setPassword($account, $validated['password']);
            } catch (Throwable $e) {
                return back()->withErrors(['password' => 'Falha ao atualizar a senha: '.$e->getMessage()], 'updatePassword');
            }

            return back()->with('status', 'password-updated');
        }

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
