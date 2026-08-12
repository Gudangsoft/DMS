<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

/**
 * Navigation is managed by the same people as institutional Pages/Sliders, so
 * it reuses the 'pages.manage' permission.
 */
class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pages.manage');
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->can('pages.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('pages.manage');
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->can('pages.manage');
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->can('pages.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('pages.manage');
    }
}
