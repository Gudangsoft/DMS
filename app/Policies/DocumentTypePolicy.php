<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, DocumentType $type): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('master_data.manage');
    }

    public function update(User $user, DocumentType $type): bool
    {
        return $user->can('master_data.manage');
    }

    public function delete(User $user, DocumentType $type): bool
    {
        return $user->can('master_data.manage') && $type->documents()->doesntExist();
    }
}
