<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DocumentApprovedNotification;
use App\Notifications\DocumentRevisionRequestedNotification;
use App\Notifications\DocumentSubmittedNotification;
use App\Services\DocumentApprovalService;
use App\Services\DocumentService;
use App\Services\DocumentVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $documents;

    protected DocumentApprovalService $approvals;

    protected Unit $unit;

    protected DocumentCategory $category;

    protected DocumentType $type;

    protected User $owner;

    protected User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('documents.storage_disk'));

        $this->documents = app(DocumentService::class);
        $this->approvals = app(DocumentApprovalService::class);

        Role::firstOrCreate(['name' => UserRole::Reviewer->value, 'guard_name' => 'web']);

        $this->unit = Unit::factory()->create();
        $this->category = DocumentCategory::factory()->create();
        $this->type = DocumentType::factory()->create();
        $this->owner = User::factory()->create(['unit_id' => $this->unit->id]);
        $this->reviewer = User::factory()->create(['unit_id' => $this->unit->id]);
        $this->reviewer->assignRole(UserRole::Reviewer->value);
    }

    protected function makeDocumentData(): array
    {
        return [
            'title' => 'SOP Pengajuan Cuti',
            'description' => 'Prosedur pengajuan cuti pegawai.',
            'document_category_id' => $this->category->id,
            'document_type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
            'owner_id' => $this->owner->id,
            'year' => 2026,
            'confidentiality_level' => 'internal',
        ];
    }

    public function test_creating_a_document_stores_first_version_as_draft(): void
    {
        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');

        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);

        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertSame('1.0', $document->current_version);
        $this->assertCount(1, $document->versions);

        $version = $document->latestVersion;
        Storage::disk(config('documents.storage_disk'))->assertExists($version->file_path);
        $this->assertSame(64, strlen($version->checksum));
    }

    /**
     * A Livewire temp upload can end up empty if the browser's async upload
     * hadn't actually finished writing to disk when the form was submitted
     * (observed on the single-threaded PHP dev server). Storing it used to
     * silently record a 0-byte version that looked fine in the version
     * history but downloaded/previewed as nothing — this should fail loudly
     * instead, and leave no broken version behind.
     */
    public function test_uploading_an_empty_file_throws_instead_of_creating_a_broken_version(): void
    {
        $file = UploadedFile::fake()->create('sop.pdf', 0, 'application/pdf');

        $this->expectException(\RuntimeException::class);

        try {
            $this->documents->create($this->makeDocumentData(), $file, $this->owner);
        } finally {
            $this->assertSame(0, Document::where('title', 'SOP Pengajuan Cuti')->count());
        }
    }

    /**
     * getClientMimeType() trusts whatever Content-Type the browser/Livewire's
     * temp-upload round trip reported, which has been observed collapsing to a
     * generic application/octet-stream even for a real PDF — breaking the
     * in-browser preview (poin 17) since it only renders for
     * mime_type === 'application/pdf'. The extension (already restricted by
     * Filament's acceptedFileTypes()) is the more trustworthy source.
     */
    public function test_mime_type_is_detected_from_extension_not_the_unreliable_client_header(): void
    {
        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/octet-stream');

        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);

        $this->assertSame('application/pdf', $document->latestVersion->mime_type);
    }

    public function test_full_submit_approve_publish_cycle(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');
        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);

        $document = $this->approvals->submitForReview($document, $this->owner);
        $this->assertSame(DocumentStatus::UnderReview, $document->status);

        $approval = $document->approvals()->latest()->first();
        $this->assertSame($this->reviewer->id, $approval->reviewer_id);
        Notification::assertSentTo($this->reviewer, DocumentSubmittedNotification::class);

        $document = $this->approvals->approve($approval, $this->reviewer, 'Sudah sesuai.');
        $this->assertSame(DocumentStatus::Approved, $document->status);
        $this->assertSame($this->reviewer->id, $document->approved_by);
        Notification::assertSentTo($this->owner, DocumentApprovedNotification::class);

        $document = $this->documents->publish($document);
        $this->assertSame(DocumentStatus::Published, $document->status);
        $this->assertNotNull($document->published_at);
    }

    public function test_revision_cycle_bumps_minor_version_and_notifies_owner(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');
        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);
        $document = $this->approvals->submitForReview($document, $this->owner);
        $approval = $document->approvals()->latest()->first();

        $document = $this->approvals->requestRevision($approval, $this->reviewer, 'Perbaiki bagian lampiran.');
        $this->assertSame(DocumentStatus::Revision, $document->status);
        Notification::assertSentTo($this->owner, DocumentRevisionRequestedNotification::class);

        $newFile = UploadedFile::fake()->create('sop-revisi.pdf', 500, 'application/pdf');
        $document = $this->documents->updateWithNewFile($document, [], $newFile, $this->owner, 'Perbaikan lampiran.');

        $this->assertSame('1.1', $document->current_version);
        $this->assertCount(2, $document->versions);
    }

    public function test_version_bumps_major_number_after_first_publish(): void
    {
        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');
        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);

        $document = $this->approvals->submitForReview($document, $this->owner);
        $approval = $document->approvals()->latest()->first();
        $document = $this->approvals->approve($approval, $this->reviewer);
        $document = $this->documents->publish($document);

        // Re-open for a substantive revision after having been published once.
        $document->status = DocumentStatus::Revision;
        $document->save();

        $newFile = UploadedFile::fake()->create('sop-v2.pdf', 500, 'application/pdf');
        $document = $this->documents->updateWithNewFile($document, [], $newFile, $this->owner);

        $this->assertSame('2.0', $document->current_version);
    }

    public function test_reject_sets_status_and_records_reviewer_comment(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');
        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);
        $document = $this->approvals->submitForReview($document, $this->owner);
        $approval = $document->approvals()->latest()->first();

        $document = $this->approvals->reject($approval, $this->reviewer, 'Tidak sesuai format.');

        $this->assertSame(DocumentStatus::Rejected, $document->status);
        $this->assertSame(ApprovalStatus::Rejected, $approval->fresh()->status);
        $this->assertSame('Tidak sesuai format.', $approval->fresh()->comments);
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $file = UploadedFile::fake()->create('sop.pdf', 500, 'application/pdf');
        $document = $this->documents->create($this->makeDocumentData(), $file, $this->owner);

        $this->documents->archive($document);

        $this->assertSame(DocumentStatus::Archived, $document->fresh()->status);
        $this->assertSoftDeleted($document);

        $this->documents->restore($document);

        $this->assertNull($document->fresh()->deleted_at);
    }
}
