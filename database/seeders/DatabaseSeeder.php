<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SourceSeeder::class,
            CategorySeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $sources = Source::all();
        $categories = Category::all();

        foreach ($sources as $source) {
            $authors = Author::factory(3)->create([
                'source_id' => $source->id,
            ]);

            foreach ($authors as $author) {
                Article::factory(5)->create([
                    'source_id' => $source->id,
                    'category_id' => $categories->random()->id,
                    'author_id' => $author->id,
                ]);
            }
        }
    }
}
