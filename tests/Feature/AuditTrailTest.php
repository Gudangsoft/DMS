<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_recorded_with_ip_address(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $activity = Activity::where('log_name', 'auth')->where('description', 'login')->first();

        $this->assertNotNull($activity);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertNotEmpty($activity->properties['ip_address']);
    }

    public function test_logout_is_recorded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'description' => 'logout',
            'causer_id' => $user->id,
        ]);
    }
}
