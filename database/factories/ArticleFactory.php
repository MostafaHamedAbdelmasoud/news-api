<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'summary' => fake()->paragraph(),
            'url' => fake()->unique()->url(),
            'image_url' => fake()->imageUrl(800, 600, 'news'),
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
            'author_id' => Author::factory(),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'metadata' => null,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
