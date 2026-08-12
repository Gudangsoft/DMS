<?php

namespace Database\Factories;

use App\Enums\ConfidentialityLevel;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = DocumentCategory::inRandomOrder()->first() ?? DocumentCategory::factory()->create();

        $subcategoryId = $category->subcategories()->inRandomOrder()->value('id');

        return [
            'document_number' => null,
            'document_code' => null,
            'title' => ucfirst($this->faker->sentence(4)),
            'description' => $this->faker->paragraph(),
            'document_category_id' => $category->id,
            'document_subcategory_id' => $subcategoryId,
            'document_type_id' => DocumentType::inRandomOrder()->value('id') ?? DocumentType::factory(),
            'unit_id' => Unit::inRandomOrder()->value('id') ?? Unit::factory(),
            'owner_id' => User::factory(),
            'year' => (int) $this->faker->numberBetween(now()->year - 3, now()->year),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expired_date' => null,
            'confidentiality_level' => $this->faker->randomElement(ConfidentialityLevel::cases()),
            'is_public' => false,
        ];
    }
}
