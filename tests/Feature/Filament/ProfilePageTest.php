<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\EditProfile;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        return $admin;
    }

    public function test_profile_page_renders_with_avatar_and_extra_fields(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get('/admin/profile');

        $response->assertOk();
        $response->assertSee('Foto Profil');
        $response->assertSee('Jabatan');
        $response->assertSee('Unit Kerja');
        $response->assertSee('Keamanan');
    }

    public function test_can_update_name_position_and_avatar(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $admin->update(['unit_id' => Unit::factory()->create()->id]);
        $this->actingAs($admin);

        $avatar = UploadedFile::fake()->image('me.jpg', 400, 400);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Nama Baru',
                'position' => 'Kepala UPT',
                'avatar' => $avatar,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $admin->refresh();

        $this->assertSame('Nama Baru', $admin->name);
        $this->assertSame('Kepala UPT', $admin->position);
        $this->assertNotNull($admin->avatar);
        Storage::disk('public')->assertExists($admin->avatar);
        $this->assertNotNull($admin->getFilamentAvatarUrl());
    }
}
