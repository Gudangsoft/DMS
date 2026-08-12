<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', Setting::get('does_not_exist', 'fallback'));
    }

    public function test_set_then_get_returns_the_new_value(): void
    {
        Setting::set('site_name', 'Kampus Uji');

        $this->assertSame('Kampus Uji', Setting::get('site_name'));
    }

    public function test_set_overwrites_an_existing_value(): void
    {
        Setting::set('site_name', 'Awal');
        $this->assertSame('Awal', Setting::get('site_name'));

        Setting::set('site_name', 'Diperbarui');
        $this->assertSame('Diperbarui', Setting::get('site_name'));
        $this->assertSame(1, Setting::where('key', 'site_name')->count());
    }
}
