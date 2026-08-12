<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Undang-Undang',
            'Peraturan Pemerintah',
            'Permendikbud',
            'Surat Keputusan',
            'SOP',
            'Pedoman',
            'Kebijakan',
            'Manual',
            'Instruksi Kerja',
            'Formulir',
            'Laporan',
        ];

        foreach ($types as $name) {
            DocumentType::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
