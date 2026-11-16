<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;

class DocumentCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, DocumentCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function update(User $user, DocumentCategory $category): bool
    {
        return $user->can('master_data.manage');
    }

    public function delete(User $user, DocumentCategory $category): bool
    {
        return $user->can('master_data.manage')
            && $category->documents()->doesntExist()
            && $category->subcategories()->doesntExist();
    }

    public function restore(User $user, DocumentCategory $category): bool
    {
        return $user->can('master_data.manage');
    }

    public function forceDelete(User $user, DocumentCategory $category): bool
    {
        return $user->can('master_data.manage') && $category->deletionBlocker() === null;
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('master_data.manage');
    }
}
