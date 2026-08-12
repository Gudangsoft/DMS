<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DocumentsPerUnitSheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return app(ReportService::class)->documentsPerUnit();
    }

    public function headings(): array
    {
        return ['Unit', 'Jumlah Dokumen'];
    }

    public function title(): string
    {
        return 'Per Unit';
    }
}
