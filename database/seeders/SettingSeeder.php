<?php

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Concerns\MembacaDataInstansi;
use Illuminate\Database\Seeder;

/**
 * Nilai awal Pengaturan Web. Nilai instansi dari data/instansi/setting.json
 * ({"key": "value"}) menimpa isi netral di bawah bila berkas itu ada.
 */
class SettingSeeder extends Seeder
{
    use MembacaDataInstansi;

    public function run(): void
    {
        $defaults = [
            'site_name' => ['Document Management System', 'identity'],
            'site_tagline' => ['Nama Institusi', 'identity'],
            'footer_text' => ['Pusat pengelolaan dokumen resmi perguruan tinggi — terstruktur, aman, dan mudah ditelusuri.', 'identity'],
            'contact_email' => ['', 'contact'],
            'contact_phone' => ['', 'contact'],
            'contact_address' => ['', 'contact'],
            'social_facebook' => ['', 'social'],
            'social_instagram' => ['', 'social'],
            'social_twitter' => ['', 'social'],
            'social_youtube' => ['', 'social'],
        ];

        foreach ($this->dataInstansi('setting.json') ?? [] as $key => $value) {
            $defaults[$key] = [$value, $defaults[$key][1] ?? 'general'];
        }

        foreach ($defaults as $key => [$value, $group]) {
            // firstOrCreate: don't clobber values an admin has already customized.
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }
    }
}
