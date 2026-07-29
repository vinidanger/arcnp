<?php

namespace App\Domain\Hosting\Policies;

use App\Domain\Hosting\Models\HostingAccount;
use App\Models\User;

class HostingAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, HostingAccount $account): bool
    {
        return $user->isAdmin();
    }
}
