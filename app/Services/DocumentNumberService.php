<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Unit;

class DocumentNumberService
{
    /**
     * Poin 44 — format contoh: DMS/{UNIT}/{CATEGORY}/{YEAR}/{NUMBER}, misal
     * DMS/LPM/SOP/2026/0001. The admin may still type a document number manually
     * instead of calling this (see DocumentResource's "Generate" form action).
     */
    public function generate(Unit $unit, DocumentCategory $category, int $year): string
    {
        $sequence = Document::withTrashed()
            ->where('unit_id', $unit->id)
            ->where('document_category_id', $category->id)
            ->where('year', $year)
            ->count() + 1;

        return str_replace(
            ['{UNIT}', '{CATEGORY}', '{YEAR}', '{NUMBER}'],
            [$unit->code, $category->code, (string) $year, str_pad((string) $sequence, 4, '0', STR_PAD_LEFT)],
            (string) config('documents.number_format')
        );
    }
}
