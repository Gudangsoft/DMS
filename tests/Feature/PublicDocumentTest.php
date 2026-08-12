<?php

namespace Tests\Feature;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function publishedDocumentWithFile(ConfidentialityLevel $level = ConfidentialityLevel::PublicLevel): Document
    {
        Storage::fake(config('documents.storage_disk'));

        $document = Document::factory()->create(['confidentiality_level' => $level]);
        $document->status = DocumentStatus::Published;
        $document->published_at = now();
        $document->save();

        $path = 'test/'.uniqid().'.pdf';
        Storage::disk(config('documents.storage_disk'))->put($path, '%PDF-1.4 fake content');

        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'version' => '1.0',
            'file_path' => $path,
            'file_name' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
        ]);

        return $document->fresh();
    }

    public function test_guest_can_view_and_download_a_public_document(): void
    {
        $document = $this->publishedDocumentWithFile(ConfidentialityLevel::PublicLevel);

        $this->get(route('documents.show', $document->uuid))->assertOk();

        $response = $this->get(route('documents.download', $document->uuid));
        $response->assertOk();

        $this->assertSame(1, $document->fresh()->download_count);
        $this->assertDatabaseHas('document_downloads', ['document_id' => $document->id]);
    }

    public function test_guest_cannot_view_an_internal_document(): void
    {
        $document = $this->publishedDocumentWithFile(ConfidentialityLevel::Internal);

        $this->get(route('documents.show', $document->uuid))->assertForbidden();
    }

    public function test_preview_streams_pdf_inline_without_counting_as_a_download(): void
    {
        $document = $this->publishedDocumentWithFile(ConfidentialityLevel::PublicLevel);

        $response = $this->get(route('documents.preview', $document->uuid));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(0, $document->fresh()->download_count);
    }

    protected function publishedDocumentWithLink(string $url = 'https://example.test/regulasi.pdf'): Document
    {
        $document = Document::factory()->create(['confidentiality_level' => ConfidentialityLevel::PublicLevel]);
        $document->status = DocumentStatus::Published;
        $document->published_at = now();
        $document->save();

        DocumentVersion::factory()->link($url)->create([
            'document_id' => $document->id,
            'version' => '1.0',
        ]);

        return $document->fresh();
    }

    public function test_preview_redirects_to_external_url_for_a_link_sourced_document(): void
    {
        $document = $this->publishedDocumentWithLink('https://example.test/regulasi.pdf');

        $response = $this->get(route('documents.preview', $document->uuid));

        $response->assertRedirect('https://example.test/regulasi.pdf');
    }

    public function test_download_redirects_to_external_url_and_still_counts_for_a_link_sourced_document(): void
    {
        $document = $this->publishedDocumentWithLink('https://example.test/regulasi.pdf');

        $response = $this->get(route('documents.download', $document->uuid));

        $response->assertRedirect('https://example.test/regulasi.pdf');
        $this->assertSame(1, $document->fresh()->download_count);
        $this->assertDatabaseHas('document_downloads', ['document_id' => $document->id]);
    }

    public function test_show_page_offers_a_view_button_instead_of_pdf_embed_for_a_link_sourced_document(): void
    {
        $document = $this->publishedDocumentWithLink();

        $response = $this->get(route('documents.show', $document->uuid));

        $response->assertOk();
        $response->assertSee('Lihat Dokumen');
        $response->assertDontSee('pdf-viewer', false);
    }

    public function test_qr_code_endpoint_returns_a_png_image(): void
    {
        $document = $this->publishedDocumentWithFile(ConfidentialityLevel::PublicLevel);

        $response = $this->get(route('documents.qr-code', $document->uuid));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_verify_document_shows_valid_for_published_document(): void
    {
        $document = $this->publishedDocumentWithFile();

        $response = $this->get(route('verify-document', $document->uuid));

        $response->assertOk();
        $response->assertSee('VALID', false);
    }

    public function test_verify_document_shows_invalid_for_unknown_uuid(): void
    {
        $response = $this->get(route('verify-document', 'not-a-real-uuid'));

        $response->assertOk();
        $response->assertSee('TIDAK VALID');
    }

    public function test_documents_catalog_and_search_render(): void
    {
        $this->publishedDocumentWithFile();

        $this->get(route('documents.index'))->assertOk();
        $this->get(route('documents.search', ['q' => 'test']))->assertOk();
    }

    public function test_categories_index_and_show_render(): void
    {
        $document = $this->publishedDocumentWithFile();

        $this->get(route('categories.index'))->assertOk();
        $this->get(route('categories.show', $document->category->code))->assertOk();
    }

    public function test_category_show_page_groups_documents_by_year_and_shows_descriptions(): void
    {
        $older = $this->publishedDocumentWithFile();
        $older->update(['description' => 'Deskripsi dokumen lama', 'year' => 2023]);

        $newer = Document::factory()->create([
            'document_category_id' => $older->document_category_id,
            'confidentiality_level' => ConfidentialityLevel::PublicLevel,
            'description' => 'Deskripsi dokumen terbaru',
            'year' => 2026,
        ]);
        $newer->status = DocumentStatus::Published;
        $newer->published_at = now();
        $newer->save();

        DocumentVersion::factory()->create(['document_id' => $newer->id, 'version' => '1.0']);

        $response = $this->get(route('categories.show', $older->category->code));

        $response->assertOk();
        $response->assertSeeInOrder(['2026', 'Deskripsi dokumen terbaru', '2023', 'Deskripsi dokumen lama']);
    }

    public function test_restricted_document_requires_direct_permission_on_frontend(): void
    {
        $document = $this->publishedDocumentWithFile(ConfidentialityLevel::Restricted);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('documents.show', $document->uuid))
            ->assertForbidden();

        $user->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'documents.access_restricted', 'guard_name' => 'web',
        ]));

        $this->actingAs($user)
            ->get(route('documents.show', $document->uuid))
            ->assertOk();
    }
}
