<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = ucfirst($this->faker->sentence(3));

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'title' => $title,
            'content' => '<p>'.implode('</p><p>', $this->faker->paragraphs(3)).'</p>',
            'excerpt' => $this->faker->sentence(),
            'featured_image' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
