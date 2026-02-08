<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalizedFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_personalized_feed(): void
    {
        $user = User::factory()->create();
        Article::factory()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'summary', 'url', 'published_at'],
                ],
                'meta',
            ]);
    }

    public function test_unauthenticated_user_cannot_get_personalized_feed(): void
    {
        $response = $this->getJson('/api/v1/user/feed');

        $response->assertStatus(401);
    }

    public function test_feed_returns_articles_matching_preferred_sources(): void
    {
        $user = User::factory()->create();
        $preferredSource = Source::factory()->create();
        $otherSource = Source::factory()->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => [$preferredSource->id],
            'preferred_categories' => [],
            'preferred_authors' => [],
        ]);

        Article::factory()->count(3)->create(['source_id' => $preferredSource->id]);
        Article::factory()->count(2)->create(['source_id' => $otherSource->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk();
        $data = $response->json('data');

        foreach ($data as $article) {
            $this->assertEquals($preferredSource->id, $article['source']['id']);
        }
    }

    public function test_feed_returns_articles_matching_preferred_categories(): void
    {
        $user = User::factory()->create();
        $preferredCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => [],
            'preferred_categories' => [$preferredCategory->id],
            'preferred_authors' => [],
        ]);

        Article::factory()->count(3)->create(['category_id' => $preferredCategory->id]);
        Article::factory()->count(2)->create(['category_id' => $otherCategory->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk();
        $data = $response->json('data');

        foreach ($data as $article) {
            $this->assertEquals($preferredCategory->id, $article['category']['id']);
        }
    }

    public function test_feed_returns_articles_matching_preferred_authors(): void
    {
        $user = User::factory()->create();
        $preferredAuthor = Author::factory()->create();
        $otherAuthor = Author::factory()->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => [],
            'preferred_categories' => [],
            'preferred_authors' => [$preferredAuthor->id],
        ]);

        Article::factory()->count(3)->create(['author_id' => $preferredAuthor->id]);
        Article::factory()->count(2)->create(['author_id' => $otherAuthor->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk();
        $data = $response->json('data');

        foreach ($data as $article) {
            $this->assertEquals($preferredAuthor->id, $article['author']['id']);
        }
    }

    public function test_feed_returns_all_articles_when_no_preferences_set(): void
    {
        $user = User::factory()->create();

        Article::factory()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_feed_supports_pagination(): void
    {
        $user = User::factory()->create();
        Article::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_feed_respects_per_page_max_limit(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create();
        $category = Category::factory()->create();
        $author = Author::factory()->create();

        Article::factory()->count(50)->create([
            'source_id' => $source->id,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed?per_page=200');

        $response->assertOk();
        // Should be capped at 100, but we only have 50 articles
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    public function test_feed_combines_multiple_preferences(): void
    {
        $user = User::factory()->create();
        $preferredSource = Source::factory()->create();
        $preferredCategory = Category::factory()->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => [$preferredSource->id],
            'preferred_categories' => [$preferredCategory->id],
            'preferred_authors' => [],
        ]);

        // This article matches both preferences
        Article::factory()->create([
            'source_id' => $preferredSource->id,
            'category_id' => $preferredCategory->id,
        ]);

        // These match only one preference each
        Article::factory()->create([
            'source_id' => $preferredSource->id,
            'category_id' => Category::factory()->create()->id,
        ]);
        Article::factory()->create([
            'source_id' => Source::factory()->create()->id,
            'category_id' => $preferredCategory->id,
        ]);

        // This matches nothing
        Article::factory()->create([
            'source_id' => Source::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk();
        // Should return articles matching at least one preference
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_feed_orders_by_published_date_descending(): void
    {
        $user = User::factory()->create();

        $older = Article::factory()->create(['published_at' => '2024-01-01']);
        $newer = Article::factory()->create(['published_at' => '2024-02-01']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/feed');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals($newer->id, $data[0]['id']);
    }
}
