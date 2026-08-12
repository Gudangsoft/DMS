<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuItemResourceTest extends TestCase
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

        MenuItem::factory()->count(2)->create();

        Livewire::test(ListMenuItems::class)->assertSuccessful();
    }

    public function test_can_create_a_top_level_menu_item(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateMenuItem::class)
            ->fillForm([
                'label' => 'Kontak Kami',
                'url' => '/page/kontak',
                'location' => 'header',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_items', ['label' => 'Kontak Kami', 'url' => '/page/kontak']);
    }

    public function test_can_create_a_submenu_item_with_a_parent(): void
    {
        $this->actingAs($this->admin());

        $parent = MenuItem::factory()->create(['label' => 'Layanan', 'location' => 'header']);

        Livewire::test(CreateMenuItem::class)
            ->fillForm([
                'label' => 'Layanan A',
                'url' => '/documents',
                'location' => 'header',
                'parent_id' => $parent->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $child = MenuItem::where('label', 'Layanan A')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($parent->fresh()->children->contains('id', $child->id));
    }

    /**
     * Header/Footer are now separate tabs instead of one flat table with a
     * filter dropdown, specifically so drag-reordering can never mix items
     * from the two locations together.
     */
    public function test_tabs_scope_the_list_to_one_location_at_a_time(): void
    {
        $this->actingAs($this->admin());

        MenuItem::factory()->create(['label' => 'Beranda', 'location' => 'header']);
        MenuItem::factory()->create(['label' => 'Kontak', 'location' => 'footer']);

        Livewire::test(ListMenuItems::class)
            ->set('activeTab', 'header')
            ->assertCanSeeTableRecords(MenuItem::where('location', 'header')->get())
            ->assertCanNotSeeTableRecords(MenuItem::where('location', 'footer')->get());

        Livewire::test(ListMenuItems::class)
            ->set('activeTab', 'footer')
            ->assertCanSeeTableRecords(MenuItem::where('location', 'footer')->get())
            ->assertCanNotSeeTableRecords(MenuItem::where('location', 'header')->get());
    }

    public function test_reordering_persists_new_sort_order_within_a_location(): void
    {
        $this->actingAs($this->admin());

        $first = MenuItem::factory()->create(['label' => 'A', 'location' => 'header', 'sort_order' => 1]);
        $second = MenuItem::factory()->create(['label' => 'B', 'location' => 'header', 'sort_order' => 2]);

        Livewire::test(ListMenuItems::class)
            ->set('activeTab', 'header')
            ->call('reorderTable', [$second->id, $first->id]);

        $this->assertTrue($second->fresh()->sort_order < $first->fresh()->sort_order);
    }

    /**
     * A child's own sort_order is only ever compared against its siblings
     * under the same parent (see MenuItem::children()) — reordering a flat,
     * mixed parent+child list is safe as long as each parent's children stay
     * in the same relative order to each other, which this checks for.
     */
    public function test_reordering_preserves_relative_order_of_children_under_the_same_parent(): void
    {
        $this->actingAs($this->admin());

        $parent = MenuItem::factory()->create(['label' => 'Tentang', 'location' => 'header', 'sort_order' => 1]);
        $childA = MenuItem::factory()->create(['label' => 'Sejarah', 'location' => 'header', 'parent_id' => $parent->id, 'sort_order' => 1]);
        $childB = MenuItem::factory()->create(['label' => 'Visi Misi', 'location' => 'header', 'parent_id' => $parent->id, 'sort_order' => 2]);
        $other = MenuItem::factory()->create(['label' => 'Dokumen', 'location' => 'header', 'sort_order' => 2]);

        Livewire::test(ListMenuItems::class)
            ->set('activeTab', 'header')
            ->call('reorderTable', [$other->id, $parent->id, $childA->id, $childB->id]);

        $orderedChildren = $parent->fresh()->children()->pluck('id')->all();
        $this->assertSame([$childA->id, $childB->id], $orderedChildren);
    }

    public function test_saving_edit_form_redirects_back_to_the_list(): void
    {
        $this->actingAs($this->admin());

        $item = MenuItem::factory()->create(['label' => 'Sambutan Ketua']);

        Livewire::test(EditMenuItem::class, ['record' => $item->getRouteKey()])
            ->fillForm(['label' => 'Sambutan Ketua Updated'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(MenuItemResource::getUrl('index'));
    }

    public function test_staff_cannot_manage_menu_items(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);

        $this->assertFalse($staff->can('create', MenuItem::class));
    }
}
