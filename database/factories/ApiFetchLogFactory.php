<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiFetchLog>
 */
class ApiFetchLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(['newsapi', 'guardian', 'nytimes']),
            'status' => 'success',
            'articles_fetched' => fake()->numberBetween(10, 100),
            'articles_created' => fake()->numberBetween(5, 50),
            'articles_updated' => fake()->numberBetween(0, 10),
            'error_message' => null,
            'fetched_at' => now(),
        ];
    }

    public function failed(string $error = 'API request failed'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'articles_fetched' => 0,
            'articles_created' => 0,
            'articles_updated' => 0,
            'error_message' => $error,
        ]);
    }
}
