<?php

namespace App\Policies;

use App\Models\AssessorPackage;
use App\Models\User;

/**
 * A package exposes its documents to anyone holding the link, bypassing
 * confidentiality levels — so only staff trusted to publish documents may
 * create or manage packages.
 */
class AssessorPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.publish');
    }

    public function view(User $user, AssessorPackage $package): bool
    {
        return $user->can('documents.publish');
    }

    public function create(User $user): bool
    {
        return $user->can('documents.publish');
    }

    public function update(User $user, AssessorPackage $package): bool
    {
        return $user->can('documents.publish');
    }

    public function delete(User $user, AssessorPackage $package): bool
    {
        return $user->can('documents.publish');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('documents.publish');
    }
}
