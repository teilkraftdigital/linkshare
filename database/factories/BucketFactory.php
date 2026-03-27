<?php

namespace Database\Factories;

use App\Models\Bucket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bucket>
 */
class BucketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'color' => 'gray',
            'is_inbox' => false,
        ];
    }

    public function inbox(): static
    {
        return $this->state(['name' => 'Inbox', 'color' => 'gray', 'is_inbox' => true]);
    }
}
