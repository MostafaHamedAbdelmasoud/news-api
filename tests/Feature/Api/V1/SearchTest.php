<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Elasticsearch for tests - will use MySQL fallback
        config(['elasticsearch.enabled' => false]);
    }

    public function test_can_search_articles_by_keyword(): void
    {
        Article::factory()->create(['title' => 'Laravel Framework Guide']);
        Article::factory()->create(['title' => 'PHP Best Practices']);
        Article::factory()->create(['content' => 'Learn Laravel today']);

        $response = $this->getJson('/api/v1/articles/search?keyword=Laravel');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_articles_by_content(): void
    {
        Article::factory()->create(['content' => 'This article discusses machine learning techniques']);
        Article::factory()->create(['content' => 'Web development best practices']);

        $response = $this->getJson('/api/v1/articles/search?keyword=machine learning');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_search_articles_by_summary(): void
    {
        Article::factory()->create(['summary' => 'A comprehensive guide to Docker']);
        Article::factory()->create(['summary' => 'Understanding Kubernetes']);

        $response = $this->getJson('/api/v1/articles/search?keyword=Docker');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_with_source_filter(): void
    {
        $source = Source::factory()->create();

        Article::factory()->create([
            'title' => 'Laravel News',
            'source_id' => $source->id,
        ]);
        Article::factory()->create([
            'title' => 'Laravel Updates',
            'source_id' => Source::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/v1/articles/search?keyword=Laravel&source_ids[]={$source->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_with_category_filter(): void
    {
        $category = Category::factory()->create();

        Article::factory()->create([
            'title' => 'Tech News Today',
            'category_id' => $category->id,
        ]);
        Article::factory()->create([
            'title' => 'Tech Innovations',
            'category_id' => Category::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/v1/articles/search?keyword=Tech&category_ids[]={$category->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_with_author_filter(): void
    {
        $author = Author::factory()->create();

        Article::factory()->create([
            'title' => 'Programming Tips',
            'author_id' => $author->id,
        ]);
        Article::factory()->create([
            'title' => 'Programming Guide',
            'author_id' => Author::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/v1/articles/search?keyword=Programming&author_ids[]={$author->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_with_date_filter(): void
    {
        Article::factory()->create([
            'title' => 'News from January',
            'published_at' => '2024-01-15',
        ]);
        Article::factory()->create([
            'title' => 'News from February',
            'published_at' => '2024-02-15',
        ]);

        $response = $this->getJson('/api/v1/articles/search?keyword=News&date_from=2024-02-01');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_returns_paginated_results(): void
    {
        Article::factory()->count(20)->create(['title' => 'Test Article']);

        $response = $this->getJson('/api/v1/articles/search?keyword=Test&per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_search_returns_empty_for_no_matches(): void
    {
        Article::factory()->create(['title' => 'Something Else']);

        $response = $this->getJson('/api/v1/articles/search?keyword=NonexistentTerm');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_is_case_insensitive(): void
    {
        Article::factory()->create(['title' => 'UPPERCASE TITLE']);
        Article::factory()->create(['title' => 'lowercase title']);

        $response = $this->getJson('/api/v1/articles/search?keyword=title');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_search_with_combined_filters(): void
    {
        $source = Source::factory()->create();
        $category = Category::factory()->create();

        Article::factory()->create([
            'title' => 'Matching Article',
            'source_id' => $source->id,
            'category_id' => $category->id,
            'published_at' => '2024-01-15',
        ]);

        Article::factory()->create([
            'title' => 'Matching but wrong source',
            'source_id' => Source::factory()->create()->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson(
            "/api/v1/articles/search?keyword=Matching&source_ids[]={$source->id}&category_ids[]={$category->id}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
