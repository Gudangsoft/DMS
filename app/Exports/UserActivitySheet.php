<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserActivitySheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        return app(ReportService::class)->userActivity();
    }

    public function map($row): array
    {
        return [
            $row->causer?->name ?? 'Unknown',
            $row->causer?->email ?? '-',
            $row->total,
        ];
    }

    public function headings(): array
    {
        return ['User', 'Email', 'Jumlah Aktivitas'];
    }

    public function title(): string
    {
        return 'Aktivitas User';
    }
}
