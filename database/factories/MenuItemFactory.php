<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'label' => ucfirst($this->faker->words(2, true)),
            'url' => '/'.$this->faker->slug(2),
            'location' => 'header',
            'open_in_new_tab' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
