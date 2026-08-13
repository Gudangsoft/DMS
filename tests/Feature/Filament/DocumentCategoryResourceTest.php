<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentCategoryResourceTest extends TestCase
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

    public function test_edit_form_preloads_existing_cover_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $path = UploadedFile::fake()->image('cover.jpg')->store('categories', 'public');
        $category = DocumentCategory::factory()->create(['cover_image' => $path]);

        Livewire::test(EditDocumentCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['cover_image' => $path]);
    }

    public function test_can_update_cover_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $category = DocumentCategory::factory()->create(['cover_image' => null]);
        $newCover = UploadedFile::fake()->image('new-cover.jpg');

        Livewire::test(EditDocumentCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['cover_image' => $newCover])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($category->fresh()->cover_image);
        Storage::disk('public')->assertExists($category->fresh()->cover_image);
    }

    /**
     * Panel-wide default set in AdminPanelProvider (resourceEditPageRedirect /
     * resourceCreatePageRedirect: 'index') — this pins it down for a resource
     * other than MenuItem to make sure the panel-wide setting, not a one-off
     * per-resource override, is what's doing the work.
     */
    public function test_saving_edit_form_redirects_back_to_the_list(): void
    {
        $this->actingAs($this->admin());

        $category = DocumentCategory::factory()->create();

        Livewire::test(EditDocumentCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['name' => 'Kategori Diperbarui'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(DocumentCategoryResource::getUrl('index'));
    }

    public function test_saving_create_form_redirects_back_to_the_list(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateDocumentCategory::class)
            ->fillForm([
                'code' => 'TST',
                'name' => 'Kategori Baru',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(DocumentCategoryResource::getUrl('index'));
    }

    public function test_categories_index_shows_cover_image_when_set(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('cover.jpg')->store('categories', 'public');
        DocumentCategory::factory()->create(['is_active' => true, 'cover_image' => $path]);

        $response = $this->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee(Storage::disk('public')->url($path), false);
    }

    public function test_category_show_page_renders_cover_banner_when_set(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('cover.jpg')->store('categories', 'public');
        $category = DocumentCategory::factory()->create(['is_active' => true, 'cover_image' => $path]);

        $response = $this->get(route('categories.show', $category->code));

        $response->assertOk();
        $response->assertSee(Storage::disk('public')->url($path), false);
    }
}
