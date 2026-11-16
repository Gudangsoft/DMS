<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\DocumentStatsOverview;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_widget_renders_all_cards(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DocumentStatsOverview::class)
            ->assertSeeInOrder(['Total Dokumen', 'Published', 'Total Download', 'Total User', 'Draft', 'Pending Approval', 'Approved', 'Rejected']);
    }
}
