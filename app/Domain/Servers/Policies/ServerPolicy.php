<?php

namespace App\Domain\Servers\Policies;

use App\Domain\Servers\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Server $server): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Server $server): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->isAdmin();
    }
}
