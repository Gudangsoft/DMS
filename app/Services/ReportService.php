<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Poin 27 — single source of truth for report data, shared by the on-screen
 * Reports page and every export (Excel/CSV/PDF) so the numbers never drift
 * between what's displayed and what's downloaded.
 */
class ReportService
{
    public function documentsPerYear(): Collection
    {
        return Document::withTrashed()
            ->selectRaw('year, count(*) as total')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();
    }

    public function documentsPerUnit(): Collection
    {
        return Document::withTrashed()
            ->join('units', 'units.id', '=', 'documents.unit_id')
            ->selectRaw('units.name as unit_name, count(*) as total')
            ->groupBy('units.id', 'units.name')
            ->orderByDesc('total')
            ->get();
    }

    public function documentsPerCategory(): Collection
    {
        return Document::withTrashed()
            ->join('document_categories', 'document_categories.id', '=', 'documents.document_category_id')
            ->selectRaw('document_categories.name as category_name, count(*) as total')
            ->groupBy('document_categories.id', 'document_categories.name')
            ->orderByDesc('total')
            ->get();
    }

    public function statusSummary(): array
    {
        return [
            'pending' => Document::where('status', 'under_review')->count(),
            'approved' => Document::where('status', 'approved')->count(),
            'published' => Document::where('status', 'published')->count(),
            'archived' => Document::withTrashed()->where('status', 'archived')->count(),
        ];
    }

    public function mostDownloaded(int $limit = 10): Collection
    {
        return Document::withTrashed()
            ->orderByDesc('download_count')
            ->limit($limit)
            ->get(['id', 'title', 'document_number', 'download_count']);
    }

    public function userActivity(): Collection
    {
        return Activity::query()
            ->selectRaw('causer_id, count(*) as total')
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->with('causer:id,name,email')
            ->orderByDesc('total')
            ->get();
    }
}
