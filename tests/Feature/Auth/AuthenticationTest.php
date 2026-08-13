<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\MathCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generates a real captcha challenge (same as visiting /login would) and
     * returns the login payload with the correct answer already filled in.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withCaptcha(array $data): array
    {
        $challenge = MathCaptcha::generate('login');

        return [...$data, 'captcha' => $challenge['a'] + $challenge['b']];
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_fails_with_a_wrong_captcha_answer(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        MathCaptcha::generate('login');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => -1,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('captcha');
    }

    public function test_users_with_a_panel_role_are_redirected_to_the_admin_panel(): void
    {
        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);

        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole(UserRole::SuperAdmin->value);

        $response = $this->post('/login', $this->withCaptcha([
            'email' => $user->email,
            'password' => 'password',
        ]));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_viewer_role_is_redirected_to_the_homepage(): void
    {
        Role::firstOrCreate(['name' => UserRole::Viewer->value, 'guard_name' => 'web']);

        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole(UserRole::Viewer->value);

        $response = $this->post('/login', $this->withCaptcha([
            'email' => $user->email,
            'password' => 'password',
        ]));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', $this->withCaptcha([
            'email' => $user->email,
            'password' => 'wrong-password',
        ]));

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'password']);

        $response = $this->post('/login', $this->withCaptcha([
            'email' => $user->email,
            'password' => 'password',
        ]));

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
