<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Models\Slider;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SliderResourceTest extends TestCase
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

    public function test_list_page_renders(): void
    {
        $this->actingAs($this->admin());

        Slider::factory()->count(2)->create();

        Livewire::test(ListSliders::class)->assertSuccessful();
    }

    public function test_can_create_a_slide(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlider::class)
            ->fillForm([
                'title' => 'Selamat Datang',
                'subtitle' => 'Pusat dokumen resmi kampus.',
                'button_text' => 'Jelajahi',
                'button_url' => '/documents',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sliders', ['title' => 'Selamat Datang']);
    }

    public function test_edit_form_preloads_existing_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $path = UploadedFile::fake()->image('existing.jpg')->store('sliders', 'public');
        $slider = Slider::factory()->create(['image' => $path]);

        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->assertFormSet(['image' => $path]);
    }

    public function test_staff_cannot_create_slides(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);

        $this->assertFalse($staff->can('create', Slider::class));
    }
}
