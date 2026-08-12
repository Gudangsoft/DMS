<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\WebsiteSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        return $admin;
    }

    public function test_super_admin_can_access_settings_page(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(WebsiteSettings::class)->assertSuccessful();
    }

    public function test_staff_cannot_access_settings_page(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);

        $this->assertFalse(WebsiteSettings::canAccess());
    }

    public function test_saving_settings_persists_and_updates_values(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(WebsiteSettings::class)
            ->fillForm(['site_name' => 'DMS Kasih Bangsa Uji'])
            ->call('save');

        $this->assertSame('DMS Kasih Bangsa Uji', Setting::get('site_name'));
    }

    public function test_form_preloads_existing_logo(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $path = UploadedFile::fake()->image('logo.jpg')->store('settings', 'public');
        Setting::set('site_logo', $path);

        Livewire::test(WebsiteSettings::class)
            ->assertFormSet(['site_logo' => $path]);
    }

    public function test_saving_theme_colors_persists_and_applies_to_public_layout(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'site_name' => 'DMS Kasih Bangsa Uji',
                'theme_header_color' => '#112233',
                'theme_body_color' => '#eeeeee',
                'theme_footer_color' => '#f5f5f5',
                'theme_accent_color' => '#ff8800',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#112233', Setting::get('theme_header_color'));
        $this->assertSame('#eeeeee', Setting::get('theme_body_color'));
        $this->assertSame('#f5f5f5', Setting::get('theme_footer_color'));
        $this->assertSame('#ff8800', Setting::get('theme_accent_color'));

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('background-color: #112233', $html);
        $this->assertStringContainsString('background-color: #eeeeee', $html);
        $this->assertStringContainsString('background-color: #f5f5f5', $html);
        $this->assertStringContainsString('--color-brand-gold: #ff8800', $html);
    }

    public function test_public_layout_falls_back_to_default_colors_when_unset(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('background-color: #0b2545', $html);
        $this->assertStringContainsString('background-color: #f9fafb', $html);
        $this->assertStringContainsString('background-color: #ffffff', $html);
        $this->assertStringContainsString('--color-brand-gold: #d4af37', $html);
    }
}
