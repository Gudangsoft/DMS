<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Pengaturan lebih dulu: seeder konten memakai {nama_institusi} dari site_tagline.
            SettingSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitSeeder::class,
            DocumentCategorySeeder::class,
            DocumentSubcategorySeeder::class,
            DocumentTypeSeeder::class,
            UserSeeder::class,
            PageSeeder::class,
            SliderSeeder::class,
            MenuItemSeeder::class,
        ]);
    }
}
