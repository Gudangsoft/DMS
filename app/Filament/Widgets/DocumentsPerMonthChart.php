<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Poin 3 — "Grafik Dokumen per Bulan": jumlah dokumen yang masuk (dibuat)
 * setiap bulan dalam 12 bulan terakhir.
 */
class DocumentsPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Dokumen per Bulan';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();

        $counts = Document::withTrashed()
            ->where('created_at', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('count(*) as total'))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M Y');
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Dokumen Masuk',
                    'data' => $data,
                    'borderColor' => '#0b2545',
                    'backgroundColor' => 'rgba(11, 37, 69, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
