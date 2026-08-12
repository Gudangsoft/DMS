<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentApproval>
 */
class DocumentApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'reviewer_id' => User::factory(),
            'status' => ApprovalStatus::Pending,
            'comments' => null,
            'reviewed_at' => null,
        ];
    }
}
