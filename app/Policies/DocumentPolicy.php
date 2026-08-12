<?php

namespace App\Policies;

use App\Enums\ConfidentialityLevel;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Listing endpoints (Filament resource index, frontend catalog) — actual row
     * visibility is still narrowed per-record by view(), and by query scopes for
     * "own documents only" style listings.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Poin 15 — access levels: PUBLIC, INTERNAL, UNIT, RESTRICTED, CONFIDENTIAL.
     * Documents that haven't finished the approval workflow yet (draft/submitted/
     * under_review/revision/rejected) are only visible to whoever is involved in
     * producing or reviewing them, regardless of confidentiality_level.
     */
    public function view(?User $user, Document $document): bool
    {
        if (! $document->status->isPublicFacing()) {
            return $this->isWorkflowParticipant($user, $document);
        }

        if ($document->is_public) {
            return true;
        }

        return match ($document->confidentiality_level) {
            ConfidentialityLevel::PublicLevel => true,
            ConfidentialityLevel::Internal => $user !== null,
            ConfidentialityLevel::Unit => $user !== null
                && ($user->unit_id === $document->unit_id || $user->can('documents.view_any')),
            ConfidentialityLevel::Restricted => $user !== null
                && ($user->can('documents.access_restricted') || $user->can('documents.view_any')),
            ConfidentialityLevel::Confidential => $user !== null
                && $user->can('documents.access_confidential'),
        };
    }

    /**
     * Download follows the same visibility rule as view(), but additionally
     * requires an explicit download-capable permission — poin 18: "Download
     * hanya dapat dilakukan jika user memiliki permission".
     */
    public function download(?User $user, Document $document): bool
    {
        if (! $this->view($user, $document)) {
            return false;
        }

        if (! $document->status->isPublicFacing()) {
            return $user !== null && $user->can('documents.download');
        }

        if ($document->is_public) {
            return true;
        }

        return match ($document->confidentiality_level) {
            ConfidentialityLevel::PublicLevel => true,
            ConfidentialityLevel::Internal => $user !== null && $user->can('documents.download'),
            ConfidentialityLevel::Unit => $user !== null && $user->can('documents.download'),
            ConfidentialityLevel::Restricted => $user !== null
                && ($user->can('documents.access_restricted') || $user->can('documents.view_any')),
            ConfidentialityLevel::Confidential => $user !== null
                && $user->can('documents.access_confidential'),
        };
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    /**
     * Poin 39: draft hanya diedit owner/admin; submitted tidak boleh diedit sampai
     * reviewer minta revisi; approved tidak boleh diubah langsung.
     */
    public function update(User $user, Document $document): bool
    {
        if (! $document->status->isEditable()) {
            return false;
        }

        return $user->id === $document->owner_id || $user->can('documents.update');
    }

    public function delete(User $user, Document $document): bool
    {
        if ($document->status->value === 'draft' && $user->id === $document->owner_id) {
            return true;
        }

        return $user->can('documents.delete');
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('documents.restore');
    }

    /**
     * Poin 26: "Permanent Delete hanya Super Admin" — handled entirely by the
     * Gate::before bypass in AppServiceProvider, so a non-Super-Admin never
     * reaches this method with a truthy result.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    public function submit(User $user, Document $document): bool
    {
        return in_array($document->status->value, ['draft', 'revision'], true)
            && $user->id === $document->owner_id
            && $user->can('documents.submit');
    }

    public function review(User $user, Document $document): bool
    {
        return $document->status->value === 'under_review' && $user->can('documents.review');
    }

    public function approve(User $user, Document $document): bool
    {
        return $document->status->value === 'under_review' && $user->can('documents.approve');
    }

    public function reject(User $user, Document $document): bool
    {
        return $document->status->value === 'under_review' && $user->can('documents.reject');
    }

    public function requestRevision(User $user, Document $document): bool
    {
        return $document->status->value === 'under_review' && $user->can('documents.request_revision');
    }

    public function publish(User $user, Document $document): bool
    {
        return $document->status->value === 'approved' && $user->can('documents.publish');
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->can('documents.archive');
    }

    public function comment(User $user, Document $document): bool
    {
        return $this->isWorkflowParticipant($user, $document) && $user->can('documents.comment');
    }

    protected function isWorkflowParticipant(?User $user, Document $document): bool
    {
        return $user !== null && (
            $user->id === $document->owner_id
            || $user->id === $document->created_by
            || $user->can('documents.view_any')
            || $document->approvals()->where('reviewer_id', $user->id)->exists()
        );
    }
}
