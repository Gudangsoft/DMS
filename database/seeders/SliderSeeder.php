<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

/**
 * Contoh slide header beranda — tanpa gambar (pakai gradient navy/gold bawaan
 * di frontend), tinggal diedit/upload gambar lewat menu "Slider Beranda" di admin.
 */
class SliderSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'title' => 'Document Management System',
                'subtitle' => 'Pusat pengelolaan dokumen resmi STIE Kasih Bangsa — terstruktur, aman, dan mudah ditelusuri.',
                'button_text' => 'Jelajahi Dokumen',
                'button_url' => '/documents',
                'sort_order' => 1,
            ],
            [
                'title' => 'Proses Approval Berjenjang',
                'subtitle' => 'Setiap dokumen melalui alur review dan persetujuan sebelum dipublikasikan secara resmi.',
                'button_text' => 'Lihat Kategori',
                'button_url' => '/categories',
                'sort_order' => 2,
            ],
            [
                'title' => 'Verifikasi Dokumen dengan QR Code',
                'subtitle' => 'Setiap dokumen resmi memiliki kode QR unik untuk memastikan keasliannya kapan saja.',
                'button_text' => 'Pelajari Lebih Lanjut',
                'button_url' => '/page/tentang-dms',
                'sort_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            Slider::updateOrCreate(
                ['title' => $slide['title']],
                [
                    'subtitle' => $slide['subtitle'],
                    'button_text' => $slide['button_text'],
                    'button_url' => $slide['button_url'],
                    'sort_order' => $slide['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
