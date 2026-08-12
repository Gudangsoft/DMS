<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_reports_page(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        Document::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(Reports::class)->assertSuccessful();
    }

    public function test_staff_without_reports_permission_cannot_access(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(UserRole::Staff->value);

        $this->assertFalse(Reports::canAccess());
    }

    public function test_excel_export_action_streams_a_download(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($admin);

        Document::factory()->count(2)->create();

        Livewire::test(Reports::class)
            ->callAction('export_excel')
            ->assertSuccessful();
    }

    public function test_pdf_export_action_streams_a_download(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($admin);

        Document::factory()->count(2)->create();

        Livewire::test(Reports::class)
            ->callAction('export_pdf')
            ->assertSuccessful();
    }
}
