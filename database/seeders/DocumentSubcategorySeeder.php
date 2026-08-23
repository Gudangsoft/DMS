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
            'A' => [
                ['code' => 'A01', 'name' => 'Regulasi Pemerintah'],
                ['code' => 'A02', 'name' => 'Visi Misi Tujuan Strategi (VMTS)'],
                ['code' => 'A03', 'name' => 'Rencana Induk Pengembangan'],
                ['code' => 'A04', 'name' => 'Statuta'],
                ['code' => 'A05', 'name' => 'SOTK (Struktur Organisasi dan Tata Kerja)'],
                ['code' => 'A06', 'name' => 'Rencana Strategis (Renstra)'],
                ['code' => 'A07', 'name' => 'Rencana Operasional (Renop)'],
                ['code' => 'A08', 'name' => 'RKAT (Rencana Kerja dan Anggaran Tahunan)'],
                ['code' => 'A09', 'name' => 'Resbang Dosen & Tependik'],
                ['code' => 'A10', 'name' => 'Panduan Akreditasi'],
            ],
            'B' => [
                ['code' => 'B01', 'name' => 'Kebijakan SPMI'],
                ['code' => 'B02', 'name' => 'Manual SPMI'],
                ['code' => 'B03', 'name' => 'Standar SPMI'],
                ['code' => 'B04', 'name' => 'Formulir SPMI'],
                ['code' => 'B05', 'name' => 'SOP SPMI'],
                ['code' => 'B06', 'name' => 'SOTK SPMI'],
            ],
            'C' => [
                ['code' => 'C01', 'name' => 'Audit Laporan Keuangan'],
                ['code' => 'C02', 'name' => 'Audit Mutu Internal'],
                ['code' => 'C03', 'name' => 'Laporan Hasil Kepuasan Mahasiswa'],
                ['code' => 'C04', 'name' => 'Laporan Hasil Kepuasan Pengguna'],
                ['code' => 'C05', 'name' => 'Laporan Hasil Kepuasan Tendik dan Tependik'],
                ['code' => 'C06', 'name' => 'Laporan Hasil VMTS STIE Kasih Bangsa'],
                ['code' => 'C07', 'name' => 'Monitoring & Evaluasi Pendidikan'],
                ['code' => 'C08', 'name' => 'Laporan Tinjauan Kurikulum'],
                ['code' => 'C09', 'name' => 'Sarana & Prasarana'],
                ['code' => 'C10', 'name' => 'Laporan Evaluasi Kerjasama'],
                ['code' => 'C11', 'name' => 'Laporan LPPM'],
                ['code' => 'C12', 'name' => 'Laporan Kegiatan Hasil PMB'],
                ['code' => 'C13', 'name' => 'Laporan PKKMB'],
                ['code' => 'C14', 'name' => 'Laporan Wisuda dan Pembukaan Tabung Visi Mimpi Wisudawan'],
                ['code' => 'C15', 'name' => 'Laporan RTM'],
                ['code' => 'C16', 'name' => 'Laporan RTL'],
            ],
            'D' => [
                ['code' => 'D01', 'name' => 'SK Ketua STIE Kasih Bangsa'],
                ['code' => 'D02', 'name' => 'SK Wakil Ketua'],
                ['code' => 'D03', 'name' => 'SK Ketua Prodi Manajemen'],
                ['code' => 'D04', 'name' => 'SK Ketua Prodi Akuntansi'],
                ['code' => 'D05', 'name' => 'SK Ketua LPPM'],
            ],
            'E' => [
                ['code' => 'E01', 'name' => 'Akreditasi Program Studi Manajemen'],
                ['code' => 'E02', 'name' => 'Akreditasi Program Studi Akuntansi'],
                ['code' => 'E03', 'name' => 'Akreditasi Institusi STIE Kasih Bangsa'],
            ],
            'F' => [
                ['code' => 'F01', 'name' => 'Pedoman Akademik'],
                ['code' => 'F02', 'name' => 'Pedoman Non Akademik'],
            ],
            'G' => [
                ['code' => 'G01', 'name' => 'Satgas PPKPT'],
            ],
            'H' => [
                ['code' => 'H01', 'name' => 'Tracer Study'],
                ['code' => 'H02', 'name' => 'Buku Wisuda'],
            ],
            'I' => [
                ['code' => 'I01', 'name' => 'Dunia Usaha'],
                ['code' => 'I02', 'name' => 'Lembaga Pendidikan'],
            ],
            'J' => [
                ['code' => 'J01', 'name' => 'Materi'],
                ['code' => 'J02', 'name' => 'Video'],
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
