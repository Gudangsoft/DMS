<?php

namespace Tests\Feature;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Enums\DocumentValidity;
use App\Enums\UserRole;
use App\Exceptions\NotEmptyException;
use App\Filament\Pages\DataQuality;
use App\Filament\Pages\ReorderDocuments;
use App\Filament\Resources\AssessorPackages\Pages\CreateAssessorPackage;
use App\Filament\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\AssessorPackage;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentExpiringNotification;
use App\Services\DocumentContentExtractor;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class DocumentLifecycleFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('documents.storage_disk'));

        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => UserRole::AdminDokumen->value, 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::SuperAdmin->value);
    }

    protected function publishedDocument(array $attributes = [], ?string $fileContents = '%PDF-1.4 fake', string $fileName = 'dokumen.pdf'): Document
    {
        $document = Document::factory()->create(array_merge([
            'confidentiality_level' => ConfidentialityLevel::PublicLevel,
        ], $attributes));
        $document->forceFill(['status' => DocumentStatus::Published, 'published_at' => now()])->save();

        if ($fileContents !== null) {
            $path = 'test/'.uniqid().'-'.$fileName;
            Storage::disk(config('documents.storage_disk'))->put($path, $fileContents);
            DocumentVersion::factory()->create([
                'document_id' => $document->id,
                'file_path' => $path,
                'file_name' => $fileName,
                'mime_type' => str_ends_with($fileName, '.pdf') ? 'application/pdf'
                    : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        return $document->fresh();
    }

    // 1. Pengaman hapus + tong sampah ---------------------------------------

    public function test_category_with_subcategories_cannot_be_deleted_even_by_super_admin(): void
    {
        $this->actingAs($this->admin);
        $category = DocumentCategory::factory()->create();
        DocumentSubcategory::factory()->create(['document_category_id' => $category->id]);

        Livewire::test(EditDocumentCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified('Tidak bisa dihapus');

        $this->assertNotSoftDeleted($category);

        $this->expectException(NotEmptyException::class);
        $category->delete();
    }

    public function test_subcategory_with_documents_cannot_be_deleted(): void
    {
        $sub = DocumentSubcategory::factory()->create();
        $doc = Document::factory()->create([
            'document_category_id' => $sub->document_category_id,
            'document_subcategory_id' => $sub->id,
        ]);

        try {
            $sub->delete();
            $this->fail('Expected NotEmptyException');
        } catch (NotEmptyException) {
        }

        $this->assertNotSoftDeleted($sub);
        $this->assertSame($sub->id, $doc->fresh()->document_subcategory_id);
    }

    public function test_empty_category_goes_to_recycle_bin_and_can_be_restored(): void
    {
        $category = DocumentCategory::factory()->create();

        $category->delete();
        $this->assertSoftDeleted($category);

        $category->restore();
        $this->assertNotSoftDeleted($category);
    }

    // 2. Cek kualitas data ----------------------------------------------------

    public function test_data_quality_page_lists_documents_whose_title_year_mismatches(): void
    {
        $this->actingAs($this->admin);
        $wrong = $this->publishedDocument(['title' => 'Statuta Tahun 1998', 'year' => 2023]);
        $right = $this->publishedDocument(['title' => 'Statuta Tahun 2022', 'year' => 2022]);

        Livewire::test(DataQuality::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$wrong])
            ->assertCanNotSeeTableRecords([$right]);
    }

    public function test_data_quality_flags_missing_files(): void
    {
        $this->actingAs($this->admin);
        $doc = $this->publishedDocument();
        Storage::disk(config('documents.storage_disk'))->delete($doc->latestVersion->file_path);

        Livewire::test(DataQuality::class)
            ->call('setCheck', 'missing_file')
            ->assertCanSeeTableRecords([$doc]);
    }

    // 3. Keberlakuan ----------------------------------------------------------

    public function test_superseded_document_links_to_its_replacement_and_sinks_below_valid_ones(): void
    {
        $sub = DocumentSubcategory::factory()->create();
        $base = ['document_category_id' => $sub->document_category_id, 'document_subcategory_id' => $sub->id];

        $new = $this->publishedDocument($base + ['title' => 'Statuta Baru 2026', 'year' => 2026]);
        $old = $this->publishedDocument($base + ['title' => 'Statuta Lama Teratas', 'year' => 2027]);
        $old->update(['validity' => DocumentValidity::Digantikan, 'superseded_by_id' => $new->id]);

        $this->get(route('documents.show', $old->uuid))
            ->assertOk()
            ->assertSee('sudah digantikan')
            ->assertSee('Statuta Baru 2026');

        $this->get(route('documents.show', $new->uuid))
            ->assertSee('Dokumen ini menggantikan')
            ->assertSee('Statuta Lama Teratas');

        $this->get(route('documents.index', ['subcategory' => $sub->id]))
            ->assertSeeInOrder(['Statuta Baru 2026', 'Statuta Lama Teratas']);
    }

    public function test_changing_validity_away_from_superseded_clears_the_replacement(): void
    {
        $new = $this->publishedDocument();
        $old = $this->publishedDocument();
        $old->update(['validity' => DocumentValidity::Digantikan, 'superseded_by_id' => $new->id]);

        $old->update(['validity' => DocumentValidity::Berlaku]);

        $this->assertNull($old->fresh()->superseded_by_id);
    }

    // 4. Pengingat kadaluarsa -------------------------------------------------

    public function test_expiring_documents_notify_owner_and_admins_once(): void
    {
        Notification::fake();

        $doc = $this->publishedDocument(['expired_date' => today()->addDays(10)]);
        $farAway = $this->publishedDocument(['expired_date' => today()->addDays(90)]);
        $docAdmin = User::factory()->create();
        $docAdmin->assignRole(UserRole::AdminDokumen->value);

        $this->artisan('dms:notify-expiring')->assertSuccessful();

        Notification::assertSentTo([$doc->owner, $this->admin, $docAdmin], DocumentExpiringNotification::class);
        Notification::assertNotSentTo($farAway->owner, DocumentExpiringNotification::class);
        $this->assertNotNull($doc->fresh()->expiry_notified_at);

        // Second run: already notified.
        Notification::fake();
        $this->artisan('dms:notify-expiring')->assertSuccessful();
        Notification::assertNothingSent();

        // Changing the date re-arms the reminder.
        $doc->fresh()->update(['expired_date' => today()->addDays(20)]);
        $this->assertNull($doc->fresh()->expiry_notified_at);
    }

    public function test_dashboard_widget_lists_expiring_documents(): void
    {
        $this->actingAs($this->admin);
        $soon = $this->publishedDocument(['title' => 'SK Segera Habis', 'expired_date' => today()->addDays(5)]);
        $this->publishedDocument(['title' => 'SK Masih Lama', 'expired_date' => today()->addYear()]);

        $this->assertTrue(\App\Filament\Widgets\ExpiringDocumentsTable::canView());

        Livewire::test(\App\Filament\Widgets\ExpiringDocumentsTable::class)
            ->assertCanSeeTableRecords([$soon])
            ->assertSee('5 hari lagi')
            ->assertDontSee('SK Masih Lama');

        $this->get('/admin')->assertOk();
    }

    // 5. Paket asesor ---------------------------------------------------------

    public function test_assessor_package_link_shows_only_its_documents_and_serves_files(): void
    {
        $inPackage = $this->publishedDocument([
            'title' => 'Dokumen Rahasia Untuk Asesor',
            'confidentiality_level' => ConfidentialityLevel::Confidential,
        ]);
        $outside = $this->publishedDocument(['title' => 'Dokumen Di Luar Paket']);

        $package = AssessorPackage::create(['name' => 'Akreditasi Prodi', 'expires_at' => now()->addWeek()]);
        $package->documents()->attach($inPackage);

        $this->get(route('assessor-packages.show', $package->token))
            ->assertOk()
            ->assertSee('Dokumen Rahasia Untuk Asesor')
            ->assertDontSee('Dokumen Di Luar Paket');

        $this->get(route('assessor-packages.file', [$package->token, $inPackage->uuid]))->assertOk();
        $this->get(route('assessor-packages.file', [$package->token, $outside->uuid]))->assertNotFound();
        $this->assertSame(1, $inPackage->fresh()->download_count);

        $zip = $this->get(route('assessor-packages.zip', $package->token));
        $zip->assertOk();
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($zip->baseResponse->getFile()->getPathname()));
        $this->assertSame(1, $archive->numFiles);
        $archive->close();
    }

    public function test_expired_or_unknown_assessor_package_is_refused(): void
    {
        $package = AssessorPackage::create(['name' => 'Lama', 'expires_at' => now()->subDay()]);

        $this->get(route('assessor-packages.show', $package->token))->assertStatus(410);
        $this->get(route('assessor-packages.show', 'token-salah'))->assertNotFound();
    }

    public function test_admin_can_create_assessor_package(): void
    {
        $this->actingAs($this->admin);
        $doc = $this->publishedDocument();

        Livewire::test(CreateAssessorPackage::class)
            ->fillForm(['name' => 'Paket Uji', 'documents' => [$doc->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = AssessorPackage::firstWhere('name', 'Paket Uji');
        $this->assertNotNull($package);
        $this->assertSame(40, strlen($package->token));
        $this->assertTrue($package->documents->contains($doc));
    }

    // 6. Atur urutan (drag & drop) -----------------------------------------------

    public function test_reorder_page_saves_drag_and_drop_order(): void
    {
        $this->actingAs($this->admin);
        $sub = DocumentSubcategory::factory()->create();
        $base = ['document_category_id' => $sub->document_category_id, 'document_subcategory_id' => $sub->id];
        $a = $this->publishedDocument($base + ['title' => 'Dok A']);
        $b = $this->publishedDocument($base + ['title' => 'Dok B']);
        $c = $this->publishedDocument($base + ['title' => 'Dok C']);

        Livewire::test(ReorderDocuments::class)
            ->set('menu', "sub:{$sub->id}")
            ->assertSee('Dok A')
            ->call('saveOrder', [$c->id, $a->id, $b->id]);

        $this->assertSame([1, 2, 3], [$c->fresh()->sort_order, $a->fresh()->sort_order, $b->fresh()->sort_order]);

        $this->get(route('documents.index', ['subcategory' => $sub->id]))
            ->assertSeeInOrder(['Dok C', 'Dok A', 'Dok B']);

        Livewire::test(ReorderDocuments::class)->set('menu', "sub:{$sub->id}")->call('resetOrder');
        $this->assertNull($a->fresh()->sort_order);
    }

    public function test_pins_are_ignored_on_the_all_documents_listing(): void
    {
        $this->publishedDocument(['title' => 'Dipin Tapi Lama', 'year' => 2010, 'sort_order' => 1]);
        $this->publishedDocument(['title' => 'Terbaru Tanpa Pin', 'year' => 2026]);

        $this->get(route('documents.index'))
            ->assertSeeInOrder(['Terbaru Tanpa Pin', 'Dipin Tapi Lama']);
    }

    // 7. Edit massal ------------------------------------------------------------

    public function test_bulk_edit_moves_documents_and_sets_year(): void
    {
        $this->actingAs($this->admin);
        $target = DocumentSubcategory::factory()->create();
        $docs = Document::factory()->count(2)->create();

        Livewire::test(ListDocuments::class)
            ->callTableBulkAction('bulk_move', $docs, data: [
                'document_category_id' => $target->document_category_id,
                'document_subcategory_id' => $target->id,
            ])
            ->assertHasNoTableBulkActionErrors();

        Livewire::test(ListDocuments::class)
            ->callTableBulkAction('bulk_year', $docs, data: ['year' => 2019]);

        foreach ($docs as $doc) {
            $doc->refresh();
            $this->assertSame($target->id, $doc->document_subcategory_id);
            $this->assertSame(2019, $doc->year);
        }
    }

    // 8. Pencarian isi dokumen --------------------------------------------------

    public function test_search_finds_words_inside_a_docx_and_shows_a_snippet(): void
    {
        $docx = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>Pasal 7 mengatur tentang kurikulum merdeka belajar kampus.</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $doc = $this->publishedDocument(['title' => 'Peraturan Akademik'], file_get_contents($docx), 'peraturan.docx');
        $this->publishedDocument(['title' => 'Dokumen Lain']);

        app(DocumentContentExtractor::class)->index($doc);

        $this->get(route('documents.index', ['q' => 'merdeka belajar']))
            ->assertOk()
            ->assertSee('Peraturan Akademik')
            ->assertSee('Ditemukan di isi dokumen')
            ->assertSee('<mark', false)
            ->assertDontSee('Dokumen Lain');
    }

    public function test_pdf_text_is_extracted(): void
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<p>Standar mutu penelitian dan pengabdian</p>')->output();
        $doc = $this->publishedDocument([], $pdf, 'standar.pdf');

        $content = app(DocumentContentExtractor::class)->index($doc);

        $this->assertStringContainsString('penelitian', (string) $content->content);
    }

    public function test_index_command_indexes_existing_documents(): void
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<p>Rencana strategis</p>')->output();
        $doc = $this->publishedDocument([], $pdf, 'renstra.pdf');

        $this->artisan('dms:index-content')->assertSuccessful();

        $this->assertStringContainsString('strategis', (string) $doc->content()->value('content'));
    }

    // 9. Populer ------------------------------------------------------------------

    public function test_home_shows_most_downloaded_documents(): void
    {
        $popular = $this->publishedDocument(['title' => 'Dokumen Sangat Populer']);
        $popular->forceFill(['download_count' => 999])->save();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Paling Banyak Diunduh')
            ->assertSee('Dokumen Sangat Populer');

        $this->get(route('documents.index', ['sort' => 'popular']))
            ->assertOk()
            ->assertSee('Dokumen Sangat Populer');
    }
}
