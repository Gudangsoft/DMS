<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Konten institusi untuk navbar frontend — struktur menu mengikuti pola umum
 * situs DMS perguruan tinggi (Sambutan Ketua, Tentang DMS, Struktur Organisasi,
 * Tugas & Fungsi). Isi masih placeholder dan dapat diedit lewat menu
 * "Halaman Institusi" di panel admin.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'sambutan-ketua',
                'title' => 'Sambutan Ketua STIE Kasih Bangsa',
                'excerpt' => 'Kata sambutan dari pimpinan institusi mengenai pentingnya tata kelola dokumen yang tertib dan transparan.',
                'content' => "<p>Selamat datang di Document Management System (DMS) STIE Kasih Bangsa.</p>".
                    "<p>Sistem ini kami hadirkan sebagai wujud komitmen institusi dalam menjaga tata kelola dokumen ".
                    "secara terpusat, aman, dan mudah ditelusuri — mulai dari dokumen regulasi, statuta, hingga ".
                    "dokumen mutu dan akreditasi.</p>".
                    "<p>Konten sambutan ini dapat diperbarui kapan saja melalui menu <em>Halaman Institusi</em> di panel admin.</p>",
            ],
            [
                'slug' => 'tentang-dms',
                'title' => 'Tentang Document Management System',
                'excerpt' => 'Penjelasan singkat mengenai tujuan dan cakupan penggunaan DMS di lingkungan STIE Kasih Bangsa.',
                'content' => "<p>Document Management System (DMS) adalah sistem terpusat untuk mengelola seluruh dokumen ".
                    "resmi perguruan tinggi, meliputi dokumen acuan, dokumen mutu, dokumen akademik, dokumen ".
                    "akreditasi, dan dokumen administrasi.</p>".
                    "<p>Setiap dokumen melalui proses persetujuan berjenjang, memiliki riwayat versi, serta dapat ".
                    "diverifikasi keasliannya melalui kode QR.</p>",
            ],
            [
                'slug' => 'struktur-organisasi',
                'title' => 'Struktur Organisasi',
                'excerpt' => 'Struktur unit kerja yang terlibat dalam pengelolaan dokumen institusi.',
                'content' => "<p>Pengelolaan dokumen pada DMS STIE Kasih Bangsa melibatkan berbagai unit kerja, di antaranya ".
                    "Rektorat, Lembaga Penjaminan Mutu (LPM), Lembaga Penelitian dan Pengabdian Masyarakat (LPPM), ".
                    "Biro Administrasi Akademik dan Kemahasiswaan (BAAK), serta Biro Administrasi Umum dan Keuangan (BAUK).</p>".
                    "<p>Bagan struktur organisasi lengkap dapat ditambahkan di sini oleh Admin Dokumen.</p>",
            ],
            [
                'slug' => 'tugas-fungsi',
                'title' => 'Tugas & Fungsi',
                'excerpt' => 'Pembagian tugas dan fungsi setiap peran dalam pengelolaan dokumen.',
                'content' => "<p>Setiap peran dalam DMS memiliki tugas dan fungsi yang berbeda:</p>".
                    "<ul>".
                    "<li><strong>Admin Dokumen</strong> — mengelola metadata, kategori, dan versi dokumen.</li>".
                    "<li><strong>Staff/Uploader</strong> — mengunggah dan mengajukan dokumen untuk direview.</li>".
                    "<li><strong>Reviewer/Approver</strong> — meninjau dan menyetujui dokumen sebelum dipublikasikan.</li>".
                    "<li><strong>Viewer/User</strong> — mencari dan membaca dokumen yang telah dipublikasikan.</li>".
                    "</ul>",
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'excerpt' => $page['excerpt'],
                    'content' => $page['content'],
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }
}
