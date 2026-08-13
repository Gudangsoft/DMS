<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_with_a_captcha_question(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Berapa hasil dari', false);
    }

    public function test_cannot_log_in_with_a_wrong_captcha_answer(): void
    {
        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);

        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole(UserRole::SuperAdmin->value);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
                'captcha' => -1,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha']);

        $this->assertGuest();
    }

    public function test_can_log_in_with_the_correct_captcha_answer(): void
    {
        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);

        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole(UserRole::SuperAdmin->value);

        $component = Livewire::test(Login::class);
        $challenge = $component->get('mathCaptcha');

        $component
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
                'captcha' => $challenge['a'] + $challenge['b'],
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }
}
