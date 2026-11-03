<?php

namespace Database\Seeders\Concerns;

use App\Models\Setting;
use Illuminate\Support\Facades\File;

/**
 * Paket data instansi di database/data/instansi (tidak ikut repositori).
 */
trait MembacaDataInstansi
{
    protected function dataInstansi(string $berkas): ?array
    {
        // Uji otomatis selalu memakai isi netral agar hasilnya tidak bergantung pada data lokal.
        if (app()->runningUnitTests()) {
            return null;
        }

        $path = database_path('data/instansi/'.$berkas);

        return File::exists($path) ? json_decode(File::get($path), true) : null;
    }

    /**
     * Ganti {nama_situs} dan {nama_institusi} dengan nilai Pengaturan Web.
     */
    protected function isiPlaceholder(string $teks): string
    {
        return strtr($teks, [
            '{nama_situs}' => (string) Setting::get('site_name', 'Document Management System'),
            '{nama_institusi}' => (string) Setting::get('site_tagline', ''),
        ]);
    }
}
