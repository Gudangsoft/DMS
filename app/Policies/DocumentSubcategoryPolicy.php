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
}
