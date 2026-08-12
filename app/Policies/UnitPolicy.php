<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Master data is public read (frontend filters, category browsing) — only
     * mutation is gated.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Unit $unit): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('master_data.manage');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('master_data.manage') && $unit->documents()->doesntExist();
    }
}
