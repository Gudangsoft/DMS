<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\DocumentSubcategories\Pages\EditDocumentSubcategory;
use App\Models\DocumentSubcategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentSubcategoryResourceTest extends TestCase
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

        $path = UploadedFile::fake()->image('cover.jpg')->store('subcategories', 'public');
        $subcategory = DocumentSubcategory::factory()->create(['cover_image' => $path]);

        Livewire::test(EditDocumentSubcategory::class, ['record' => $subcategory->getRouteKey()])
            ->assertFormSet(['cover_image' => $path]);
    }

    public function test_can_update_cover_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $subcategory = DocumentSubcategory::factory()->create(['cover_image' => null]);
        $newCover = UploadedFile::fake()->image('new-cover.jpg');

        Livewire::test(EditDocumentSubcategory::class, ['record' => $subcategory->getRouteKey()])
            ->fillForm(['cover_image' => $newCover])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($subcategory->fresh()->cover_image);
        Storage::disk('public')->assertExists($subcategory->fresh()->cover_image);
    }
}
