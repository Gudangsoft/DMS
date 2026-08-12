<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = $this->faker->slug().'.pdf';

        return [
            'document_id' => Document::factory(),
            'version' => '1.0',
            'source_type' => 'upload',
            'file_name' => $fileName,
            'file_path' => 'documents/'.$this->faker->uuid().'/'.$fileName,
            'file_size' => $this->faker->numberBetween(50_000, 5_000_000),
            'mime_type' => 'application/pdf',
            'checksum' => hash('sha256', $this->faker->uuid()),
            'change_notes' => $this->faker->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * A version pointing at a file hosted elsewhere instead of one on our disk.
     */
    public function link(string $url = 'https://example.test/regulasi.pdf'): static
    {
        return $this->state(fn () => [
            'source_type' => 'link',
            'file_name' => basename(parse_url($url, PHP_URL_PATH) ?? 'file.pdf'),
            'file_path' => null,
            'external_url' => $url,
            'file_size' => null,
            'mime_type' => 'application/pdf',
            'checksum' => null,
        ]);
    }
}
