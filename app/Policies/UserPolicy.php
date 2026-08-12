<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view_any');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can('users.view_any');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    /**
     * A user may never delete or deactivate their own account through the
     * Manajemen User screen — avoids locking everyone out by accident.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('users.delete') && $user->id !== $model->id;
    }
}
