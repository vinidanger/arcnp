<?php

namespace App\Domain\Hosting\Policies;

use App\Domain\Hosting\Models\HostingAccount;
use App\Models\User;

class HostingAccountPolicy
{
    /**
     * Listagem "de todas as contas" é admin-only — o cliente lista as
     * suas via query já escopada no controller do cliente, não via
     * Gate::allows('viewAny').
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin() || $account->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cobre as ações "de dono" (SSL, banco, domínios adicionais) tanto
     * no admin quanto no painel do cliente. Suspender/reativar/excluir
     * continuam expostos só nas rotas /admin (bloqueadas por middleware
     * type:admin antes mesmo de chegar na Policy), então ampliar aqui
     * não vaza essas ações pro cliente.
     */
    public function update(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin() || $account->user_id === $user->id;
    }

    public function delete(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin();
    }
}
