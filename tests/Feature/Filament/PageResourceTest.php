<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
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

        Page::factory()->count(2)->create();

        Livewire::test(ListPages::class)->assertSuccessful();
    }

    public function test_can_create_a_page(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Tentang Kami',
                'slug' => 'tentang-kami',
                'content' => '<p>Isi halaman.</p>',
                'is_published' => true,
                'show_in_menu' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'tentang-kami', 'title' => 'Tentang Kami']);
    }

    public function test_can_edit_a_page(): void
    {
        $this->actingAs($this->admin());

        $page = Page::factory()->create(['title' => 'Judul Lama']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Judul Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Baru', $page->fresh()->title);
    }

    public function test_edit_form_preloads_existing_featured_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $path = UploadedFile::fake()->image('cover.jpg')->store('pages', 'public');
        $page = Page::factory()->create(['featured_image' => $path]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet(['featured_image' => $path]);
    }

    public function test_staff_cannot_create_pages(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);

        $this->assertFalse($staff->can('create', Page::class));
    }
}
