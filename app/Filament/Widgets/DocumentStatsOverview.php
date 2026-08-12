<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Poin 3 — dashboard stat cards.
 */
class DocumentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $withArchived = fn () => Document::withTrashed();

        return [
            Stat::make('Total Dokumen', $withArchived()->count())
                ->icon('heroicon-o-document-duplicate')
                ->color('gray'),
            Stat::make('Dokumen Aktif', Document::count())
                ->description('Belum diarsipkan')
                ->icon('heroicon-o-document-check')
                ->color('info'),
            Stat::make('Draft', Document::where('status', 'draft')->count())
                ->icon('heroicon-o-pencil')
                ->color('gray'),
            Stat::make('Pending Approval', Document::where('status', 'under_review')->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Approved', Document::where('status', 'approved')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Rejected', Document::where('status', 'rejected')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Archived', $withArchived()->where('status', 'archived')->count())
                ->icon('heroicon-o-archive-box')
                ->color('gray'),
            Stat::make('Total User', User::count())
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Total Download', DocumentDownload::count())
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
        ];
    }
}
