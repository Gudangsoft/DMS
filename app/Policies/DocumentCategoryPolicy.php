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
}
