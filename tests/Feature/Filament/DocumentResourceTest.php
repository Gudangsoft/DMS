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

    public function test_can_edit_document_metadata(): void
    {
        $document = Document::factory()->create(['title' => 'Judul Lama']);

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['title' => 'Judul Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Baru', $document->fresh()->title);
    }
}
