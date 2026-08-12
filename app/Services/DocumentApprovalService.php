<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\User;
use App\Notifications\DocumentApprovedNotification;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\DocumentRevisionRequestedNotification;
use App\Notifications\DocumentSubmittedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentApprovalService
{
    /**
     * Poin 12 workflow: DRAFT/REVISION → SUBMIT → UNDER_REVIEW. Creates a pending
     * DocumentApproval and notifies the assigned reviewer (poin 20).
     */
    public function submitForReview(Document $document, User $actor, ?int $reviewerId = null): Document
    {
        if (! in_array($document->status, [DocumentStatus::Draft, DocumentStatus::Revision], true)) {
            throw new RuntimeException('Hanya dokumen berstatus draft atau revision yang dapat disubmit.');
        }

        if (! $document->versions()->exists()) {
            throw new RuntimeException('Dokumen belum memiliki file — upload file sebelum submit.');
        }

        $reviewer = $reviewerId
            ? User::findOrFail($reviewerId)
            : $this->findReviewer($document);

        if (! $reviewer) {
            throw new RuntimeException('Tidak ada reviewer aktif yang tersedia. Hubungi Super Admin.');
        }

        return DB::transaction(function () use ($document, $reviewer) {
            $approval = DocumentApproval::create([
                'document_id' => $document->id,
                'reviewer_id' => $reviewer->id,
                'status' => ApprovalStatus::Pending,
                'comments' => null,
                'reviewed_at' => null,
            ]);

            $document->status = DocumentStatus::UnderReview;
            $document->save();

            $reviewer->notify(new DocumentSubmittedNotification($document));

            return $document;
        });
    }

    /**
     * Prefer a Reviewer/Approver in the same unit as the document; fall back to
     * any active reviewer institution-wide.
     */
    protected function findReviewer(Document $document): ?User
    {
        $query = User::role(UserRole::Reviewer->value)->where('is_active', true);

        return (clone $query)->where('unit_id', $document->unit_id)->first()
            ?? $query->first();
    }

    public function approve(DocumentApproval $approval, User $actor, ?string $comments = null): Document
    {
        return DB::transaction(function () use ($approval, $actor, $comments) {
            $approval->update([
                'status' => ApprovalStatus::Approved,
                'comments' => $comments,
                'reviewed_at' => now(),
            ]);

            $document = $approval->document;
            $document->status = DocumentStatus::Approved;
            $document->approved_by = $actor->id;
            $document->approved_at = now();
            $document->save();

            $document->owner->notify(new DocumentApprovedNotification($document));

            return $document;
        });
    }

    public function reject(DocumentApproval $approval, User $actor, string $comments): Document
    {
        return DB::transaction(function () use ($approval, $actor, $comments) {
            $approval->update([
                'status' => ApprovalStatus::Rejected,
                'comments' => $comments,
                'reviewed_at' => now(),
            ]);

            $document = $approval->document;
            $document->status = DocumentStatus::Rejected;
            $document->save();

            $document->owner->notify(new DocumentRejectedNotification($approval));

            return $document;
        });
    }

    public function requestRevision(DocumentApproval $approval, User $actor, string $comments): Document
    {
        return DB::transaction(function () use ($approval, $actor, $comments) {
            $approval->update([
                'status' => ApprovalStatus::Revision,
                'comments' => $comments,
                'reviewed_at' => now(),
            ]);

            $document = $approval->document;
            $document->status = DocumentStatus::Revision;
            $document->save();

            $document->owner->notify(new DocumentRevisionRequestedNotification($approval));

            return $document;
        });
    }
}
