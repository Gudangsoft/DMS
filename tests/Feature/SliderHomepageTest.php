<?php

namespace Tests\Feature;

use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliderHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_slides_are_shown_on_homepage(): void
    {
        Slider::factory()->create(['title' => 'Slide Aktif Uji', 'is_active' => true]);
        Slider::factory()->create(['title' => 'Slide Nonaktif Uji', 'is_active' => false]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Slide Aktif Uji');
        $response->assertDontSee('Slide Nonaktif Uji');
    }

    public function test_homepage_falls_back_to_static_hero_when_no_slides(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Document Management System');
    }

    public function test_slides_are_ordered_by_sort_order(): void
    {
        Slider::factory()->create(['title' => 'Kedua', 'sort_order' => 2]);
        Slider::factory()->create(['title' => 'Pertama', 'sort_order' => 1]);

        $ordered = Slider::active()->pluck('title')->all();

        $this->assertSame(['Pertama', 'Kedua'], $ordered);
    }
}
