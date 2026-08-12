<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentApprovalService;
use App\Services\DocumentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentTableActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reviewer;

    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('documents.storage_disk'));
        Notification::fake();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::SuperAdmin->value);

        $unit = Unit::factory()->create();
        $this->reviewer = User::factory()->create(['unit_id' => $unit->id]);
        $this->reviewer->assignRole(UserRole::Reviewer->value);

        $owner = User::factory()->create(['unit_id' => $unit->id]);
        $file = UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf');

        $this->document = app(DocumentService::class)->create([
            'title' => 'Dokumen Uji Aksi',
            'document_category_id' => DocumentCategory::factory()->create()->id,
            'document_type_id' => DocumentType::factory()->create()->id,
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'year' => 2026,
            'confidentiality_level' => 'internal',
        ], $file, $owner);
    }

    public function test_submit_action_moves_document_to_under_review(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->callTableAction('submit', $this->document, data: ['reviewer_id' => $this->reviewer->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame('under_review', $this->document->fresh()->status->value);
    }

    public function test_approve_action_moves_document_to_approved(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);

        $this->actingAs($this->reviewer);

        Livewire::test(ListDocuments::class)
            ->callTableAction('approve', $this->document->fresh(), data: ['comments' => 'OK'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('approved', $this->document->fresh()->status->value);
    }

    public function test_reject_action_requires_comments_and_moves_to_rejected(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);

        $this->actingAs($this->reviewer);

        Livewire::test(ListDocuments::class)
            ->callTableAction('reject', $this->document->fresh(), data: ['comments' => 'Tidak sesuai standar.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('rejected', $this->document->fresh()->status->value);
    }

    public function test_publish_action_moves_approved_document_to_published(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);
        $approval = $this->document->approvals()->latest()->first();
        app(DocumentApprovalService::class)->approve($approval, $this->reviewer);

        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->callTableAction('publish', $this->document->fresh())
            ->assertHasNoTableActionErrors();

        $this->assertSame('published', $this->document->fresh()->status->value);
    }

    public function test_staff_cannot_see_approve_action_on_their_own_document(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);

        $this->actingAs($this->document->fresh()->owner);

        Livewire::test(ListDocuments::class)
            ->assertTableActionHidden('approve', $this->document->fresh());
    }

    /**
     * Gate::before gives Super Admin a blanket `true` on every ability check
     * (AppServiceProvider), bypassing the status checks each DocumentPolicy
     * method encodes. Without duplicating those status checks in
     * DocumentsTable's ->visible() closures, Super Admin would see every
     * workflow button on every document regardless of status — and clicking
     * one out of order would throw in the service layer instead of doing
     * nothing. These pin the buttons to only the statuses they apply to.
     */
    public function test_super_admin_only_sees_workflow_actions_valid_for_a_draft_document(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->assertTableActionVisible('submit', $this->document)
            ->assertTableActionVisible('edit', $this->document)
            ->assertTableActionHidden('approve', $this->document)
            ->assertTableActionHidden('request_revision', $this->document)
            ->assertTableActionHidden('reject', $this->document)
            ->assertTableActionHidden('publish', $this->document);
    }

    public function test_super_admin_only_sees_workflow_actions_valid_for_an_under_review_document(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);

        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->assertTableActionHidden('submit', $this->document->fresh())
            ->assertTableActionHidden('edit', $this->document->fresh())
            ->assertTableActionVisible('approve', $this->document->fresh())
            ->assertTableActionVisible('request_revision', $this->document->fresh())
            ->assertTableActionVisible('reject', $this->document->fresh())
            ->assertTableActionHidden('publish', $this->document->fresh());
    }

    public function test_super_admin_only_sees_publish_action_for_an_approved_document(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);
        $approval = $this->document->approvals()->latest()->first();
        app(DocumentApprovalService::class)->approve($approval, $this->reviewer);

        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->assertTableActionVisible('publish', $this->document->fresh())
            ->assertTableActionHidden('submit', $this->document->fresh())
            ->assertTableActionHidden('approve', $this->document->fresh());
    }

    public function test_super_admin_cannot_see_submit_or_edit_on_a_published_document(): void
    {
        app(DocumentApprovalService::class)->submitForReview($this->document, $this->admin, $this->reviewer->id);
        $approval = $this->document->approvals()->latest()->first();
        app(DocumentApprovalService::class)->approve($approval, $this->reviewer);
        app(DocumentService::class)->publish($this->document->fresh());

        $this->actingAs($this->admin);

        Livewire::test(ListDocuments::class)
            ->assertTableActionHidden('submit', $this->document->fresh())
            ->assertTableActionHidden('edit', $this->document->fresh())
            ->assertTableActionHidden('publish', $this->document->fresh());
    }
}
