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
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitSeeder::class,
            DocumentCategorySeeder::class,
            DocumentSubcategorySeeder::class,
            DocumentTypeSeeder::class,
            UserSeeder::class,
            PageSeeder::class,
            SliderSeeder::class,
            SettingSeeder::class,
            MenuItemSeeder::class,
        ]);
    }
}
