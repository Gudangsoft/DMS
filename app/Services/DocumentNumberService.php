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
     *
     * The sequence continues from the highest number already used for this
     * unit/category/year (not "count + 1", which collided once documents were
     * moved between categories or deleted) and is checked against every
     * existing number, archived ones included, so the result is always free.
     * When $current already holds a number in this series, it's kept as is.
     */
    public function generate(Unit $unit, DocumentCategory $category, int $year, ?Document $current = null): string
    {
        $format = (string) config('documents.number_format');
        $base = str_replace(
            ['{UNIT}', '{CATEGORY}', '{YEAR}'],
            [$unit->code, $category->code, (string) $year],
            $format
        );

        [$before, $after] = array_pad(explode('{NUMBER}', $base, 2), 2, '');
        $pattern = '/^'.preg_quote($before, '/').'(\d+)'.preg_quote($after, '/').'$/';

        if ($current?->document_number && preg_match($pattern, $current->document_number)) {
            return $current->document_number;
        }

        $highest = Document::withTrashed()
            ->where('document_number', 'like', addcslashes($before, '%_\\').'%')
            ->pluck('document_number')
            ->map(fn (string $number) => preg_match($pattern, $number, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        $sequence = $highest + 1;

        do {
            $number = $before.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT).$after;
            $sequence++;
        } while (Document::withTrashed()->where('document_number', $number)->exists());

        return $number;
    }
}
