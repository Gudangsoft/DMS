<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Poin 3 — dashboard stat cards. Laid out as two even rows of four:
 * headline totals on top, approval-workflow states below.
 */
class DocumentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $byStatus = Document::withTrashed()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (string $status): int => (int) ($byStatus[$status] ?? 0);
        $fmt = fn (int $n): string => number_format($n, 0, ',', '.');

        $archived = $count('archived');

        return [
            // Row 1 — headline totals
            Stat::make('Total Dokumen', $fmt($byStatus->sum()))
                ->description($archived ? "Termasuk {$fmt($archived)} diarsipkan" : 'Semua dokumen di sistem')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary'),
            Stat::make('Published', $fmt($count('published')))
                ->description('Tampil di web')
                ->icon('heroicon-o-globe-alt')
                ->color('success'),
            Stat::make('Total Download', $fmt(DocumentDownload::count()))
                ->description('Sepanjang waktu')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info'),
            Stat::make('Total User', $fmt(User::count()))
                ->description('Akun terdaftar')
                ->icon('heroicon-o-users')
                ->color('gray'),

            // Row 2 — workflow
            Stat::make('Draft', $fmt($count('draft')))
                ->description('Belum diajukan')
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
            Stat::make('Pending Approval', $fmt($count('under_review')))
                ->description('Menunggu review')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Approved', $fmt($count('approved')))
                ->description('Siap dipublikasikan')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Rejected', $fmt($count('rejected')))
                ->description('Perlu diperbaiki')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
