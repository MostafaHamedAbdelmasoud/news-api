<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferred_sources' => [],
            'preferred_categories' => [],
            'preferred_authors' => [],
        ];
    }

    public function withPreferences(array $sources = [], array $categories = [], array $authors = []): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_sources' => $sources,
            'preferred_categories' => $categories,
            'preferred_authors' => $authors,
        ]);
    }
}
