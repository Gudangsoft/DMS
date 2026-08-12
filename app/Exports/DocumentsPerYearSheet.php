<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DocumentsPerYearSheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return app(ReportService::class)->documentsPerYear();
    }

    public function headings(): array
    {
        return ['Tahun', 'Jumlah Dokumen'];
    }

    public function title(): string
    {
        return 'Per Tahun';
    }
}
