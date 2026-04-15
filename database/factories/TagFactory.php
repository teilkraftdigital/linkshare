<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'color' => 'gray',
            'is_public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    public function childOf(Tag $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'color' => $parent->color,
            'is_public' => $parent->is_public,
        ]);
    }
}
