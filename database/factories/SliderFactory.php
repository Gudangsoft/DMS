<?php

namespace Database\Factories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slider>
 */
class SliderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->sentence(4)),
            'subtitle' => $this->faker->sentence(10),
            'image' => null,
            'button_text' => 'Selengkapnya',
            'button_url' => '/documents',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
