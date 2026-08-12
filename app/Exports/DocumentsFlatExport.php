<?php

namespace App\Exports;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Poin 27 — flat "Document Report" listing, used for the CSV export (CSV has no
 * concept of multiple sheets, unlike the Excel workbook in ReportsExport).
 */
class DocumentsFlatExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Document::withTrashed()->with(['category', 'unit', 'type', 'owner']);
    }

    public function map($document): array
    {
        return [
            $document->document_number ?? '-',
            $document->document_code ?? '-',
            $document->title,
            $document->category->name,
            $document->unit->name,
            $document->type->name,
            $document->year,
            $document->status->getLabel(),
            $document->owner->name,
            $document->download_count,
            $document->created_at->format('Y-m-d'),
        ];
    }

    public function headings(): array
    {
        return [
            'Nomor', 'Kode', 'Judul', 'Kategori', 'Unit', 'Jenis',
            'Tahun', 'Status', 'Pemilik', 'Jumlah Download', 'Dibuat Pada',
        ];
    }
}
