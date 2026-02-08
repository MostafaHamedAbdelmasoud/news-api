<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'NewsAPI.org',
                'slug' => 'newsapi',
                'base_url' => 'https://newsapi.org/v2',
                'is_active' => true,
            ],
            [
                'name' => 'The Guardian',
                'slug' => 'guardian',
                'base_url' => 'https://content.guardianapis.com',
                'is_active' => true,
            ],
            [
                'name' => 'New York Times',
                'slug' => 'nytimes',
                'base_url' => 'https://api.nytimes.com/svc',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            Source::query()->updateOrCreate(
                ['slug' => $source['slug']],
                $source
            );
        }
    }
}
