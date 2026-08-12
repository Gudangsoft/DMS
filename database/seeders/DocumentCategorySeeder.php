<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'DA', 'name' => 'Dokumen Acuan', 'icon' => 'heroicon-o-book-open', 'sort_order' => 1],
            ['code' => 'DM', 'name' => 'Dokumen Mutu', 'icon' => 'heroicon-o-shield-check', 'sort_order' => 2],
            ['code' => 'DAK', 'name' => 'Dokumen Akademik', 'icon' => 'heroicon-o-academic-cap', 'sort_order' => 3],
            ['code' => 'AKR', 'name' => 'Dokumen Akreditasi', 'icon' => 'heroicon-o-trophy', 'sort_order' => 4],
            ['code' => 'ADM', 'name' => 'Dokumen Administrasi', 'icon' => 'heroicon-o-document-text', 'sort_order' => 5],
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
