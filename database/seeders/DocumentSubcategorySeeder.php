<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use Illuminate\Database\Seeder;

class DocumentSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subcategories = [
            'DA' => [
                ['code' => 'DA01', 'name' => 'Regulasi Pemerintah'],
                ['code' => 'DA02', 'name' => 'VMTS (Visi, Misi, Tujuan, Sasaran)'],
                ['code' => 'DA03', 'name' => 'Rencana Induk Pengembangan'],
                ['code' => 'DA04', 'name' => 'Statuta'],
                ['code' => 'DA05', 'name' => 'SOTK (Struktur Organisasi dan Tata Kerja)'],
                ['code' => 'DA06', 'name' => 'Renstra (Rencana Strategis)'],
                ['code' => 'DA07', 'name' => 'Renop (Rencana Operasional)'],
                ['code' => 'DA08', 'name' => 'RKAT (Rencana Kerja dan Anggaran Tahunan)'],
            ],
            'DM' => [
                ['code' => 'DM01', 'name' => 'Kebijakan Mutu'],
                ['code' => 'DM02', 'name' => 'Manual Mutu'],
                ['code' => 'DM03', 'name' => 'Standar Mutu'],
                ['code' => 'DM04', 'name' => 'SOP Mutu'],
                ['code' => 'DM05', 'name' => 'Instruksi Kerja Mutu'],
                ['code' => 'DM06', 'name' => 'Formulir Mutu'],
            ],
            'DAK' => [
                ['code' => 'DAK01', 'name' => 'Kurikulum'],
                ['code' => 'DAK02', 'name' => 'Silabus / RPS'],
                ['code' => 'DAK03', 'name' => 'Pedoman Akademik'],
                ['code' => 'DAK04', 'name' => 'Panduan Skripsi / Tugas Akhir'],
                ['code' => 'DAK05', 'name' => 'Kalender Akademik'],
            ],
            'AKR' => [
                ['code' => 'AKR01', 'name' => 'Borang Akreditasi Institusi'],
                ['code' => 'AKR02', 'name' => 'Borang Akreditasi Program Studi'],
                ['code' => 'AKR03', 'name' => 'Laporan Evaluasi Diri (LED)'],
                ['code' => 'AKR04', 'name' => 'Laporan Kinerja Program Studi (LKPS)'],
            ],
            'ADM' => [
                ['code' => 'ADM01', 'name' => 'Surat Keputusan'],
                ['code' => 'ADM02', 'name' => 'Surat Edaran'],
                ['code' => 'ADM03', 'name' => 'Surat Tugas'],
                ['code' => 'ADM04', 'name' => 'MoU / Kerja Sama'],
                ['code' => 'ADM05', 'name' => 'Laporan Kegiatan'],
            ],
        ];

        foreach ($subcategories as $categoryCode => $items) {
            $category = DocumentCategory::where('code', $categoryCode)->first();

            if (! $category) {
                continue;
            }

            foreach ($items as $sortOrder => $item) {
                DocumentSubcategory::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'document_category_id' => $category->id,
                        'name' => $item['name'],
                        'sort_order' => $sortOrder + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
