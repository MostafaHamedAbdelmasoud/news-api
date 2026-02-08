<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Business and finance news'],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Entertainment and celebrity news'],
            ['name' => 'General', 'slug' => 'general', 'description' => 'General news'],
            ['name' => 'Health', 'slug' => 'health', 'description' => 'Health and medical news'],
            ['name' => 'Science', 'slug' => 'science', 'description' => 'Science and research news'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Sports news and updates'],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Technology and innovation news'],
            ['name' => 'Politics', 'slug' => 'politics', 'description' => 'Political news and analysis'],
            ['name' => 'World', 'slug' => 'world', 'description' => 'International news'],
            ['name' => 'Environment', 'slug' => 'environment', 'description' => 'Environmental news'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'description' => 'Lifestyle and culture'],
            ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Travel news and guides'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Education news'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
