<?php

namespace App\Filament\Widgets;

use App\Models\DocumentCategory;
use Filament\Widgets\ChartWidget;

/**
 * Poin 3 — "Grafik Dokumen berdasarkan Kategori".
 */
class DocumentsByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Dokumen berdasarkan Kategori';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getData(): array
    {
        $categories = DocumentCategory::withCount('documents')
            ->orderBy('sort_order')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dokumen',
                    'data' => $categories->pluck('documents_count')->all(),
                    'backgroundColor' => [
                        '#0b2545', '#13315c', '#d4af37', '#e8c766', '#64748b', '#94a3b8',
                    ],
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
