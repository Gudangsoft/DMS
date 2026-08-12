<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DocumentsPerCategorySheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return app(ReportService::class)->documentsPerCategory();
    }

    public function headings(): array
    {
        return ['Kategori', 'Jumlah Dokumen'];
    }

    public function title(): string
    {
        return 'Per Kategori';
    }
}
