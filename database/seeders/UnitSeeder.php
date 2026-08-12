<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['code' => 'REKT', 'name' => 'Rektorat'],
            ['code' => 'WR', 'name' => 'Wakil Rektor'],
            ['code' => 'FAK', 'name' => 'Fakultas'],
            ['code' => 'PRODI', 'name' => 'Program Studi'],
            ['code' => 'LPM', 'name' => 'Lembaga Penjaminan Mutu'],
            ['code' => 'LPPM', 'name' => 'Lembaga Penelitian dan Pengabdian Masyarakat'],
            ['code' => 'BAAK', 'name' => 'Biro Administrasi Akademik dan Kemahasiswaan'],
            ['code' => 'BAUK', 'name' => 'Biro Administrasi Umum dan Keuangan'],
            ['code' => 'PERPUS', 'name' => 'Perpustakaan'],
            ['code' => 'IT', 'name' => 'Unit Teknologi Informasi'],
            ['code' => 'KEU', 'name' => 'Keuangan'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
