<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Poin 27 — "Export laporan ke Excel": one workbook, one sheet per report.
 */
class ReportsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DocumentsPerYearSheet(),
            new DocumentsPerUnitSheet(),
            new DocumentsPerCategorySheet(),
            new MostDownloadedSheet(),
            new UserActivitySheet(),
        ];
    }
}
