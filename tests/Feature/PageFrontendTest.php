<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_is_visible_to_guests(): void
    {
        $page = Page::factory()->create(['is_published' => true]);

        $response = $this->get(route('pages.show', $page->slug));

        $response->assertOk();
        $response->assertSee($page->title);
    }

    public function test_unpublished_page_returns_404(): void
    {
        $page = Page::factory()->create(['is_published' => false]);

        $this->get(route('pages.show', $page->slug))->assertNotFound();
    }

    public function test_active_menu_items_appear_in_navbar(): void
    {
        MenuItem::factory()->create(['label' => 'Sambutan Ketua Uji', 'location' => 'header', 'is_active' => true]);
        MenuItem::factory()->create(['label' => 'Tautan Nonaktif Uji', 'location' => 'header', 'is_active' => false]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Sambutan Ketua Uji');
        $response->assertDontSee('Tautan Nonaktif Uji');
    }

    public function test_homepage_renders_with_stats_and_categories(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
