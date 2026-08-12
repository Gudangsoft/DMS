<?php

namespace Database\Factories;

use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSubcategory>
 */
class DocumentSubcategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_category_id' => DocumentCategory::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('??##')),
            'name' => $this->faker->words(4, true),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
