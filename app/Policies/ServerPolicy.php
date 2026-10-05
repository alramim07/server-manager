<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function view(User $user, Server $server): bool
    {
        return true;
    }

    public function update(User $user, Server $server): bool
    {
        return $user->isAdmin() || $user->id === $server->owner_id;
    }

    public function delete(User $user, Server $server): bool
    {
        return $this->update($user, $server);
    }
}
