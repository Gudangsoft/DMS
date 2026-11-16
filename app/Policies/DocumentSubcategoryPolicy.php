<?php

namespace App\Policies;

use App\Models\DocumentSubcategory;
use App\Models\User;

class DocumentSubcategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, DocumentSubcategory $subcategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function update(User $user, DocumentSubcategory $subcategory): bool
    {
        return $user->can('master_data.manage');
    }

    public function delete(User $user, DocumentSubcategory $subcategory): bool
    {
        return $user->can('master_data.manage') && $subcategory->documents()->doesntExist();
    }

    public function restore(User $user, DocumentSubcategory $subcategory): bool
    {
        return $user->can('master_data.manage');
    }

    public function forceDelete(User $user, DocumentSubcategory $subcategory): bool
    {
        return $user->can('master_data.manage') && $subcategory->deletionBlocker() === null;
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
