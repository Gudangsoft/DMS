<?php

namespace App\Policies;

use App\Models\Slider;
use App\Models\User;

/**
 * Slider content is managed by the same people as institutional Pages, so it
 * reuses the 'pages.manage' permission rather than adding a near-duplicate one.
 */
class SliderPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Slider $slider): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('pages.manage');
    }

    public function update(User $user, Slider $slider): bool
    {
        return $user->can('pages.manage');
    }

    public function delete(User $user, Slider $slider): bool
    {
        return $user->can('pages.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('pages.manage');
    }
}
