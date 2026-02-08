<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Author>
 */
class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'source_id' => Source::factory(),
        ];
    }

    public function withoutSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_id' => null,
        ]);
    }
}
