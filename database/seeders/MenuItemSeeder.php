<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Default navigation. Header keeps only 4 top-level items (Beranda, Dokumen,
 * Kategori, Tentang) — the 4 institutional pages live under "Tentang" as a
 * dropdown, so the navbar doesn't overflow with 7+ top-level links. Footer
 * stays flat (a footer link list is expected to be exhaustive, not nested).
 */
class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $tentang = MenuItem::updateOrCreate(
            ['location' => 'header', 'label' => 'Tentang', 'parent_id' => null],
            ['url' => '#', 'sort_order' => 4, 'is_active' => true]
        );

        $header = [
            ['label' => 'Beranda', 'url' => '/', 'sort_order' => 1],
            ['label' => 'Dokumen', 'url' => '/documents', 'sort_order' => 2],
            ['label' => 'Kategori', 'url' => '/categories', 'sort_order' => 3],
        ];

        $headerSubmenu = [
            ['label' => 'Sambutan Ketua', 'url' => '/page/sambutan-ketua', 'sort_order' => 1],
            ['label' => 'Tentang DMS', 'url' => '/page/tentang-dms', 'sort_order' => 2],
            ['label' => 'Struktur Organisasi', 'url' => '/page/struktur-organisasi', 'sort_order' => 3],
            ['label' => 'Tugas & Fungsi', 'url' => '/page/tugas-fungsi', 'sort_order' => 4],
        ];

        $footer = [
            ['label' => 'Semua Dokumen', 'url' => '/documents', 'sort_order' => 1],
            ['label' => 'Kategori Dokumen', 'url' => '/categories', 'sort_order' => 2],
            ['label' => 'Pencarian', 'url' => '/search', 'sort_order' => 3],
            ['label' => 'Sambutan Ketua', 'url' => '/page/sambutan-ketua', 'sort_order' => 4],
            ['label' => 'Tentang DMS', 'url' => '/page/tentang-dms', 'sort_order' => 5],
            ['label' => 'Struktur Organisasi', 'url' => '/page/struktur-organisasi', 'sort_order' => 6],
            ['label' => 'Tugas & Fungsi', 'url' => '/page/tugas-fungsi', 'sort_order' => 7],
        ];

        foreach ($header as $item) {
            MenuItem::updateOrCreate(
                ['location' => 'header', 'label' => $item['label'], 'parent_id' => null],
                ['url' => $item['url'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        foreach ($headerSubmenu as $item) {
            MenuItem::updateOrCreate(
                ['location' => 'header', 'label' => $item['label']],
                ['url' => $item['url'], 'parent_id' => $tentang->id, 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }

        foreach ($footer as $item) {
            MenuItem::updateOrCreate(
                ['location' => 'footer', 'label' => $item['label'], 'parent_id' => null],
                ['url' => $item['url'], 'sort_order' => $item['sort_order'], 'is_active' => true]
            );
        }
    }
}
