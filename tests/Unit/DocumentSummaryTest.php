<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use App\Models\DocumentType;
use App\Models\Unit;
use Tests\TestCase;

class DocumentSummaryTest extends TestCase
{
    protected function document(array $attributes = []): Document
    {
        $document = new Document(array_merge(['year' => 2026], $attributes));
        $document->setRelation('category', new DocumentCategory(['name' => 'Dokumen Mutu']));
        $document->setRelation('subcategory', new DocumentSubcategory(['name' => 'Statuta']));
        $document->setRelation('type', new DocumentType(['name' => 'Pedoman']));
        $document->setRelation('unit', new Unit(['name' => 'Rektorat']));

        return $document;
    }

    public function test_builds_a_summary_from_metadata_when_there_is_no_description(): void
    {
        $this->assertSame(
            'Pedoman · Dokumen Mutu › Statuta. Diterbitkan oleh Rektorat, tahun 2026.',
            $this->document()->summary()
        );
    }

    public function test_leaves_out_missing_parts(): void
    {
        $document = $this->document();
        $document->setRelation('subcategory', null);

        $this->assertSame('Pedoman · Dokumen Mutu. Diterbitkan oleh Rektorat, tahun 2026.', $document->summary());
    }

    public function test_prefers_the_admin_written_description(): void
    {
        $this->assertSame('Statuta yang disahkan senat.', $this->document(['description' => 'Statuta yang disahkan senat.'])->summary());
    }
}
