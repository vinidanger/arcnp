<?php

namespace App\Rules;

use App\Domain\Hosting\Services\SshAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Substitui o `current_password` nativo do Laravel nos formulários que um
 * CLIENTE pode alcançar (trocar senha, excluir a própria conta). Admin
 * continua exatamente igual ao `current_password` (Hash::check contra
 * users.password) — só cliente confere contra ssh_password da hospedagem,
 * que é a credencial de login dele agora.
 */
class CurrentPasswordOrSsh implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            if (! Hash::check($value, $user->password)) {
                $fail('A senha informada não confere.');
            }

            return;
        }

        $account = $user->hostingAccount;

        if (! $account || ! $account->ssh_password) {
            $fail('Sua hospedagem ainda não foi provisionada — isso ainda não está disponível.');

            return;
        }

        if (! SshAccessService::verifyPassword($account, $value)) {
            $fail('A senha informada não confere.');
        }
    }
}
