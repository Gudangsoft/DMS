<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

/**
 * Mirrors the 10-category taxonomy (A-J) used by the old WordPress DMS site,
 * so the public "Kategori Dokumen" browsing structure matches it exactly —
 * see App\Console\Commands\RestructureDocumentCategoriesToWordpress, which
 * remaps already-imported documents onto these same codes.
 */
class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'A', 'name' => 'Dokumen Acuan', 'icon' => 'heroicon-o-book-open', 'sort_order' => 1],
            ['code' => 'B', 'name' => 'Dokumen SPMI', 'icon' => 'heroicon-o-shield-check', 'sort_order' => 2],
            ['code' => 'C', 'name' => 'Laporan Perguruan Tinggi', 'icon' => 'heroicon-o-chart-bar', 'sort_order' => 3],
            ['code' => 'D', 'name' => 'Surat Keputusan (SK)', 'icon' => 'heroicon-o-document-text', 'sort_order' => 4],
            ['code' => 'E', 'name' => 'Info Akreditasi', 'icon' => 'heroicon-o-trophy', 'sort_order' => 5],
            ['code' => 'F', 'name' => 'Pedoman Akademik & Non Akademik', 'icon' => 'heroicon-o-academic-cap', 'sort_order' => 6],
            ['code' => 'G', 'name' => 'Satgas PPKPT', 'icon' => 'heroicon-o-shield-exclamation', 'sort_order' => 7],
            ['code' => 'H', 'name' => 'Alumni', 'icon' => 'heroicon-o-user-group', 'sort_order' => 8],
            ['code' => 'I', 'name' => 'Mitra Kerjasama', 'icon' => 'heroicon-o-briefcase', 'sort_order' => 9],
            ['code' => 'J', 'name' => 'Sosialisasi/Pelatihan Dikti', 'icon' => 'heroicon-o-megaphone', 'sort_order' => 10],
        ];

        foreach ($categories as $category) {
            DocumentCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
