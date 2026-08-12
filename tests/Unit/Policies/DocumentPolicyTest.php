<?php

namespace Tests\Unit\Policies;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\Unit;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DocumentPolicy();

        foreach (['documents.view_any', 'documents.download', 'documents.access_restricted', 'documents.access_confidential'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    protected function publishedDocument(ConfidentialityLevel $level, ?Unit $unit = null): Document
    {
        return Document::factory()->create([
            'confidentiality_level' => $level,
            'unit_id' => $unit?->id ?? Unit::factory(),
        ])->fresh(); // fresh() to pick up DB-cast status if ever changed directly
    }

    public function test_guest_can_view_public_document(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::PublicLevel);
        $document->status = DocumentStatus::Published;

        $this->assertTrue($this->policy->view(null, $document));
    }

    public function test_guest_cannot_view_internal_document(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Internal);
        $document->status = DocumentStatus::Published;

        $this->assertFalse($this->policy->view(null, $document));
    }

    public function test_any_logged_in_user_can_view_internal_document(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Internal);
        $document->status = DocumentStatus::Published;

        $this->assertTrue($this->policy->view(User::factory()->create(), $document));
    }

    public function test_unit_document_only_visible_to_same_unit(): void
    {
        $unitA = Unit::factory()->create();
        $unitB = Unit::factory()->create();

        $document = $this->publishedDocument(ConfidentialityLevel::Unit, $unitA);
        $document->status = DocumentStatus::Published;

        $sameUnitUser = User::factory()->create(['unit_id' => $unitA->id]);
        $otherUnitUser = User::factory()->create(['unit_id' => $unitB->id]);

        $this->assertTrue($this->policy->view($sameUnitUser, $document));
        $this->assertFalse($this->policy->view($otherUnitUser, $document));
    }

    public function test_restricted_document_requires_direct_permission(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Restricted);
        $document->status = DocumentStatus::Published;

        $regularUser = User::factory()->create();
        $grantedUser = User::factory()->create();
        $grantedUser->givePermissionTo('documents.access_restricted');

        $this->assertFalse($this->policy->view($regularUser, $document));
        $this->assertTrue($this->policy->view($grantedUser, $document));
    }

    public function test_confidential_document_requires_direct_permission(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Confidential);
        $document->status = DocumentStatus::Published;

        $restrictedUser = User::factory()->create();
        $restrictedUser->givePermissionTo('documents.access_restricted');

        $confidentialUser = User::factory()->create();
        $confidentialUser->givePermissionTo('documents.access_confidential');

        // Having restricted-level access alone does NOT unlock confidential documents.
        $this->assertFalse($this->policy->view($restrictedUser, $document));
        $this->assertTrue($this->policy->view($confidentialUser, $document));
    }

    public function test_draft_document_only_visible_to_owner_and_workflow_participants(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $reviewer = User::factory()->create();

        $document = Document::factory()->create([
            'owner_id' => $owner->id,
            'confidentiality_level' => ConfidentialityLevel::PublicLevel,
        ]);
        // Draft is the default status set by the observer — confidentiality is irrelevant here.
        $this->assertTrue($document->status === DocumentStatus::Draft);

        DocumentApproval::factory()->create([
            'document_id' => $document->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $this->assertTrue($this->policy->view($owner, $document));
        $this->assertTrue($this->policy->view($reviewer, $document));
        $this->assertFalse($this->policy->view($stranger, $document));
        $this->assertFalse($this->policy->view(null, $document));
    }

    public function test_is_public_flag_overrides_confidentiality_level_for_published_documents(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Confidential);
        $document->status = DocumentStatus::Published;
        $document->is_public = true;

        $this->assertTrue($this->policy->view(null, $document));
    }

    public function test_download_requires_permission_even_when_document_is_viewable(): void
    {
        $document = $this->publishedDocument(ConfidentialityLevel::Internal);
        $document->status = DocumentStatus::Published;

        $viewerOnly = User::factory()->create();
        $downloader = User::factory()->create();
        $downloader->givePermissionTo('documents.download');

        $this->assertTrue($this->policy->view($viewerOnly, $document));
        $this->assertFalse($this->policy->download($viewerOnly, $document));
        $this->assertTrue($this->policy->download($downloader, $document));
    }
}
