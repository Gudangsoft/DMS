<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix, not a schema change. The header/footer menu was manually
 * rebuilt (via the Filament admin) into a flat layout matching the old
 * WordPress site's nav — but its links still point at the 4 originally
 * seeded placeholder Page records instead of the fuller-content "wp-*"
 * Page rows brought in by the WordPress migration (php artisan
 * wp:import-legacy). This repoints those URLs and adds the "Beranda"
 * (home) link that's missing from the live menu.
 */
return new class extends Migration
{
    protected array $urlMap = [
        '/page/sambutan-ketua' => '/page/wp-sambutan-ketua-stie-kasih-bangsa',
        '/page/tentang-dms' => '/page/wp-tentang-document-management-system-stie-kasih-bangsa',
        '/page/struktur-organisasi' => '/page/wp-struktur-organisasi',
        '/page/tugas-fungsi' => '/page/wp-tugas-fungsi',
    ];

    public function up(): void
    {
        foreach ($this->urlMap as $old => $new) {
            DB::table('menu_items')->where('url', $old)->update(['url' => $new, 'updated_at' => now()]);
        }

        $headerHasHome = DB::table('menu_items')
            ->where('location', 'header')
            ->where('url', '/')
            ->exists();

        if (! $headerHasHome) {
            DB::table('menu_items')->insert([
                'label' => 'Beranda',
                'url' => '/',
                'location' => 'header',
                'parent_id' => null,
                'open_in_new_tab' => false,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->urlMap as $old => $new) {
            DB::table('menu_items')->where('url', $new)->update(['url' => $old, 'updated_at' => now()]);
        }

        DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'Beranda')
            ->where('url', '/')
            ->delete();
    }
};
