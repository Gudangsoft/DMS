<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => ['Document Management System', 'identity'],
            'site_tagline' => ['STIE Kasih Bangsa', 'identity'],
            'footer_text' => ['Pusat pengelolaan dokumen resmi perguruan tinggi — terstruktur, aman, dan mudah ditelusuri.', 'identity'],
            'contact_email' => ['info@stiekasihbangsa.ac.id', 'contact'],
            'contact_phone' => ['', 'contact'],
            'contact_address' => ['', 'contact'],
            'social_facebook' => ['', 'social'],
            'social_instagram' => ['', 'social'],
            'social_twitter' => ['', 'social'],
            'social_youtube' => ['', 'social'],
        ];

        foreach ($defaults as $key => [$value, $group]) {
            // firstOrCreate: don't clobber values an admin has already customized.
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }
    }
}
