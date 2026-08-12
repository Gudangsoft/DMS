<?php

namespace App\Filament\Pages;

use App\Exports\DocumentsFlatExport;
use App\Exports\ReportsExport;
use App\Services\ReportService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Poin 27 — Report: ringkasan di layar + export Excel/CSV/PDF.
 */
class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $navigationLabel = 'Document Report';

    protected static ?string $title = 'Laporan';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function getViewData(): array
    {
        $reports = app(ReportService::class);

        return [
            'perYear' => $reports->documentsPerYear(),
            'perUnit' => $reports->documentsPerUnit(),
            'perCategory' => $reports->documentsPerCategory(),
            'statusSummary' => $reports->statusSummary(),
            'mostDownloaded' => $reports->mostDownloaded(),
            'userActivity' => $reports->userActivity(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-m-table-cells')
                ->color('success')
                ->action(fn () => Excel::download(new ReportsExport(), 'laporan-dms-'.now()->format('Y-m-d').'.xlsx')),

            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->action(fn () => Excel::download(new DocumentsFlatExport(), 'dokumen-'.now()->format('Y-m-d').'.csv', ExcelFormat::CSV)),

            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-m-document')
                ->color('danger')
                ->action(function () {
                    $data = $this->getViewData();

                    return response()->streamDownload(
                        fn () => print (Pdf::loadView('reports.pdf', $data)->output()),
                        'laporan-dms-'.now()->format('Y-m-d').'.pdf'
                    );
                }),
        ];
    }
}
