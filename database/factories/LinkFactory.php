<?php

namespace Database\Factories;

use App\Models\Bucket;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'notes' => null,
            'bucket_id' => Bucket::factory(),
        ];
    }

    public function withNotes(): static
    {
        return $this->state(['notes' => fake()->paragraph()]);
    }
}
