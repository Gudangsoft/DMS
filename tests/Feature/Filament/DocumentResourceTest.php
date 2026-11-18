<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('documents.storage_disk'));

        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($this->admin);
    }

    public function test_list_page_renders(): void
    {
        Document::factory()->count(3)->create();

        Livewire::test(ListDocuments::class)->assertSuccessful();
    }

    public function test_can_create_a_document_with_a_file(): void
    {
        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();
        $owner = User::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'SOP Ujian Akhir',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $owner->id,
                'year' => 2026,
                'confidentiality_level' => 'internal',
                'file' => UploadedFile::fake()->create('sop.pdf', 200, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::where('title', 'SOP Ujian Akhir')->firstOrFail();
        $this->assertSame('1.0', $document->current_version);
        $this->assertCount(1, $document->versions);
    }

    public function test_can_create_a_document_with_an_external_link(): void
    {
        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();
        $owner = User::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'Permendiktisaintek No 39/2025',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $owner->id,
                'year' => 2025,
                'confidentiality_level' => 'internal',
                'source_type' => 'link',
                'file_url' => 'https://dms.stiekasihbangsa.ac.id/wp-content/uploads/2025/10/Permendiktisaintek-39.pdf',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::where('title', 'Permendiktisaintek No 39/2025')->firstOrFail();
        $version = $document->versions()->first();

        $this->assertTrue($version->isLink());
        $this->assertSame('https://dms.stiekasihbangsa.ac.id/wp-content/uploads/2025/10/Permendiktisaintek-39.pdf', $version->external_url);
        $this->assertNull($version->file_path);
        $this->assertNull($version->checksum);
    }

    public function test_create_requires_a_link_when_link_source_selected(): void
    {
        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'Tanpa URL',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $this->admin->id,
                'year' => 2026,
                'confidentiality_level' => 'internal',
                'source_type' => 'link',
            ])
            ->call('create')
            ->assertHasFormErrors(['file_url']);
    }

    public function test_create_requires_a_file(): void
    {
        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'Tanpa File',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $this->admin->id,
                'year' => 2026,
                'confidentiality_level' => 'internal',
            ])
            ->call('create')
            ->assertHasFormErrors(['file']);
    }

    public function test_can_create_a_document_and_directly_approve_and_publish_it(): void
    {
        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();
        $owner = User::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'SOP Langsung Terbit',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $owner->id,
                'year' => 2026,
                'confidentiality_level' => 'internal',
                'file' => UploadedFile::fake()->create('sop.pdf', 200, 'application/pdf'),
                'approve_and_publish' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::where('title', 'SOP Langsung Terbit')->firstOrFail();

        $this->assertSame('published', $document->status->value);
        $this->assertSame($this->admin->id, $document->approved_by);
        $this->assertNotNull($document->approved_at);
        $this->assertNotNull($document->published_at);
        $this->assertCount(1, $document->approvals);
        $this->assertSame('approved', $document->approvals->first()->status->value);
    }

    /**
     * The toggle is only rendered when the acting user has both documents.approve
     * and documents.publish — but a spoofed submission (e.g. via devtools) must
     * still be rejected server-side in handleRecordCreation().
     */
    public function test_approve_and_publish_flag_is_ignored_without_permission(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);
        $this->actingAs($staff);

        $category = DocumentCategory::factory()->create();
        $type = DocumentType::factory()->create();
        $unit = Unit::factory()->create();

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'Percobaan Spoof Toggle',
                'document_category_id' => $category->id,
                'document_type_id' => $type->id,
                'unit_id' => $unit->id,
                'owner_id' => $staff->id,
                'year' => 2026,
                'confidentiality_level' => 'internal',
                'file' => UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf'),
                'approve_and_publish' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::where('title', 'Percobaan Spoof Toggle')->firstOrFail();

        $this->assertSame('draft', $document->status->value);
        $this->assertNull($document->approved_by);
    }

    public function test_can_edit_document_metadata(): void
    {
        $document = Document::factory()->create(['title' => 'Judul Lama']);

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['title' => 'Judul Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Baru', $document->fresh()->title);
    }

    public function test_duplicate_document_number_is_a_form_error_not_a_crash(): void
    {
        Document::factory()->create(['document_number' => 'DMS/LPM/5/2024/0013']);
        $archived = Document::factory()->create(['document_number' => 'DMS/LPM/5/2024/0099']);
        $archived->delete();
        $document = Document::factory()->create(['document_number' => 'DMS/LPM/5/2024/0001']);

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['document_number' => 'DMS/LPM/5/2024/0013'])
            ->call('save')
            ->assertHasFormErrors(['document_number' => 'unique']);

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['document_number' => 'DMS/LPM/5/2024/0099'])
            ->call('save')
            ->assertHasFormErrors(['document_number' => 'unique']);

        // Keeping its own number is fine.
        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['title' => 'Judul Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_generated_number_never_collides_with_existing_ones(): void
    {
        $unit = Unit::factory()->create(['code' => 'LPM']);
        $category = DocumentCategory::factory()->create(['code' => '5']);
        $service = app(\App\Services\DocumentNumberService::class);

        // Numbers 0001–0013 exist in the series, but only two documents still
        // sit in this unit/category/year — the old "count + 1" gave 0003/0013.
        foreach ([1, 13] as $n) {
            Document::factory()->create([
                'unit_id' => $unit->id, 'document_category_id' => $category->id, 'year' => 2024,
                'document_number' => sprintf('DMS/LPM/5/2024/%04d', $n),
            ]);
        }
        Document::factory()->create(['document_number' => 'DMS/LPM/5/2024/0014'])->delete(); // archived, moved elsewhere

        $this->assertSame('DMS/LPM/5/2024/0015', $service->generate($unit, $category, 2024));

        // A document already numbered in this series keeps its number.
        $own = Document::where('document_number', 'DMS/LPM/5/2024/0013')->first();
        $this->assertSame('DMS/LPM/5/2024/0013', $service->generate($unit, $category, 2024, $own));
    }
}
