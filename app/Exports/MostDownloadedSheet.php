<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MostDownloadedSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        return app(ReportService::class)->mostDownloaded(20);
    }

    public function map($document): array
    {
        return [
            $document->document_number ?? '-',
            $document->title,
            $document->download_count,
        ];
    }

    public function headings(): array
    {
        return ['Nomor Dokumen', 'Judul', 'Jumlah Download'];
    }

    public function title(): string
    {
        return 'Paling Banyak Didownload';
    }
}
