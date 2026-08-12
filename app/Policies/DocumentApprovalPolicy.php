<?php

namespace App\Policies;

use App\Models\DocumentApproval;
use App\Models\User;

class DocumentApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.review') || $user->can('documents.view_any');
    }

    public function view(User $user, DocumentApproval $approval): bool
    {
        return $user->id === $approval->reviewer_id
            || $user->id === $approval->document->owner_id
            || $user->can('documents.view_any');
    }

    /**
     * Approve/reject/request-revision on an existing approval record — only the
     * assigned reviewer, or anyone holding the blanket documents.approve permission
     * (e.g. Admin Dokumen covering for an absent reviewer).
     */
    public function update(User $user, DocumentApproval $approval): bool
    {
        return $approval->status->value === 'pending'
            && ($user->id === $approval->reviewer_id || $user->can('documents.approve'));
    }
}
