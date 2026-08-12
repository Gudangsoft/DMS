<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru',
            'email' => 'baru@dms.local',
            'position' => 'Kepala Unit',
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@dms.local', $user->email);
        $this->assertSame('Kepala Unit', $user->position);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this
            ->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this
            ->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_guests_cannot_access_the_profile_page(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }
}
