<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_articles(): void
    {
        Article::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'summary', 'url', 'published_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_list_articles_with_pagination(): void
    {
        Article::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/articles?per_page=5&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_can_filter_articles_by_source(): void
    {
        $source = Source::factory()->create();
        $otherSource = Source::factory()->create();

        Article::factory()->count(3)->create(['source_id' => $source->id]);
        Article::factory()->count(2)->create(['source_id' => $otherSource->id]);

        $response = $this->getJson("/api/v1/articles?source_ids[]={$source->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_articles_by_multiple_sources(): void
    {
        $source1 = Source::factory()->create();
        $source2 = Source::factory()->create();
        $source3 = Source::factory()->create();

        Article::factory()->count(2)->create(['source_id' => $source1->id]);
        Article::factory()->count(3)->create(['source_id' => $source2->id]);
        Article::factory()->count(1)->create(['source_id' => $source3->id]);

        $response = $this->getJson("/api/v1/articles?source_ids[]={$source1->id}&source_ids[]={$source2->id}");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_articles_by_category(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        Article::factory()->count(4)->create(['category_id' => $category->id]);
        Article::factory()->count(2)->create(['category_id' => $otherCategory->id]);

        $response = $this->getJson("/api/v1/articles?category_ids[]={$category->id}");

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_can_filter_articles_by_author(): void
    {
        $author = Author::factory()->create();
        $otherAuthor = Author::factory()->create();

        Article::factory()->count(3)->create(['author_id' => $author->id]);
        Article::factory()->count(4)->create(['author_id' => $otherAuthor->id]);

        $response = $this->getJson("/api/v1/articles?author_ids[]={$author->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_articles_by_date_range(): void
    {
        Article::factory()->create(['published_at' => '2024-01-15']);
        Article::factory()->create(['published_at' => '2024-01-20']);
        Article::factory()->create(['published_at' => '2024-02-01']);

        $response = $this->getJson('/api/v1/articles?date_from=2024-01-10&date_to=2024-01-25');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_articles_by_keyword(): void
    {
        Article::factory()->create(['title' => 'Laravel Framework News']);
        Article::factory()->create(['title' => 'PHP Updates']);
        Article::factory()->create(['title' => 'Laravel Tips and Tricks']);

        $response = $this->getJson('/api/v1/articles?keyword=Laravel');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_sort_articles_by_published_date_desc(): void
    {
        $older = Article::factory()->create(['published_at' => '2024-01-01']);
        $newer = Article::factory()->create(['published_at' => '2024-02-01']);

        $response = $this->getJson('/api/v1/articles?sort_by=published_at&sort_direction=desc');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($newer->id, $data[0]['id']);
    }

    public function test_can_sort_articles_by_published_date_asc(): void
    {
        $older = Article::factory()->create(['published_at' => '2024-01-01']);
        $newer = Article::factory()->create(['published_at' => '2024-02-01']);

        $response = $this->getJson('/api/v1/articles?sort_by=published_at&sort_direction=asc');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($older->id, $data[0]['id']);
    }

    public function test_can_combine_multiple_filters(): void
    {
        $source = Source::factory()->create();
        $category = Category::factory()->create();

        Article::factory()->create([
            'source_id' => $source->id,
            'category_id' => $category->id,
            'published_at' => '2024-01-15',
        ]);
        Article::factory()->create([
            'source_id' => $source->id,
            'category_id' => Category::factory()->create()->id,
        ]);
        Article::factory()->create([
            'source_id' => Source::factory()->create()->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson("/api/v1/articles?source_ids[]={$source->id}&category_ids[]={$category->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_article(): void
    {
        $article = Article::factory()->create();

        $response = $this->getJson("/api/v1/articles/{$article->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content', 'summary', 'url', 'image_url', 'published_at', 'source', 'category', 'author'],
            ])
            ->assertJsonPath('data.id', $article->id);
    }

    public function test_returns_404_for_nonexistent_article(): void
    {
        $response = $this->getJson('/api/v1/articles/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Article not found']);
    }

    public function test_soft_deleted_articles_are_not_listed(): void
    {
        Article::factory()->count(3)->create();
        $deletedArticle = Article::factory()->create();
        $deletedArticle->delete();

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_validates_invalid_source_ids(): void
    {
        $response = $this->getJson('/api/v1/articles?source_ids[]=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_ids.0']);
    }

    public function test_validates_invalid_date_range(): void
    {
        $response = $this->getJson('/api/v1/articles?date_from=2024-02-01&date_to=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_validates_per_page_max_limit(): void
    {
        $response = $this->getJson('/api/v1/articles?per_page=200');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }
}
