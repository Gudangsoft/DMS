<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Support\Tema;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_konten_bawaan_memakai_nama_institusi_dari_pengaturan(): void
    {
        $this->seed(SettingSeeder::class);
        Setting::set('site_tagline', 'Kampus Uji Coba', 'identity');
        $this->seed(PageSeeder::class);

        $this->assertSame('Sambutan Ketua Kampus Uji Coba', Page::where('slug', 'sambutan-ketua')->value('title'));
    }

    public function test_warna_utama_dan_aksen_mengikuti_pengaturan(): void
    {
        $this->get('/')->assertSee('--color-brand-navy: '.Tema::PRIMER, false);

        Setting::set('theme_primary_color', '#14532D');
        Setting::set('theme_accent_color', '#F97316');

        $this->get('/')
            ->assertSee('--color-brand-navy: #14532d', false)
            ->assertSee('--color-brand-gold: #f97316', false);
    }

    public function test_warna_tidak_valid_diabaikan(): void
    {
        Setting::set('theme_primary_color', 'red;}body{display:none');
        Setting::set('theme_header_color', 'red;}body{display:none');

        $this->get('/')->assertDontSee('display:none', false);
        $this->assertSame(Tema::PRIMER, Tema::primer());
    }

    public function test_migrasi_impor_wordpress_dilewati_tanpa_alamat_situs_lama(): void
    {
        Http::fake();
        config(['services.legacy_wordpress.url' => '']);

        $this->artisan('migrate:refresh', ['--path' => 'database/migrations/2026_08_23_125938_fix_video_materi_pelatihan_page_content.php']);
        $this->artisan('wp:import-legacy', ['--dry-run' => true])->assertFailed();

        Http::assertNothingSent();
    }
}
