<?php

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Services\ElasticsearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private ElasticsearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['elasticsearch.enabled' => true]);
        config(['elasticsearch.hosts.0.host' => 'localhost']);
        config(['elasticsearch.hosts.0.port' => 9200]);

        $this->service = new ElasticsearchService;
    }

    public function test_is_available_returns_true_when_elasticsearch_responds(): void
    {
        Http::fake([
            'localhost:9200/' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->assertTrue($this->service->isAvailable());
    }

    public function test_is_available_returns_false_when_elasticsearch_disabled(): void
    {
        config(['elasticsearch.enabled' => false]);

        $service = new ElasticsearchService;

        $this->assertFalse($service->isAvailable());
    }

    public function test_is_available_returns_false_when_elasticsearch_unreachable(): void
    {
        Http::fake([
            'localhost:9200/' => Http::response([], 500),
        ]);

        $this->assertFalse($this->service->isAvailable());
    }

    public function test_index_article_sends_correct_request(): void
    {
        Http::fake([
            'localhost:9200/news_articles/_doc/*' => Http::response(['result' => 'created'], 201),
        ]);

        $article = Article::factory()->create([
            'title' => 'Test Article',
            'content' => 'Test content',
        ]);

        $result = $this->service->indexArticle($article->id);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($article) {
            return str_contains($request->url(), "/news_articles/_doc/{$article->id}") &&
                   $request->method() === 'PUT' &&
                   $request['title'] === 'Test Article';
        });
    }

    public function test_index_article_returns_false_for_nonexistent_article(): void
    {
        $result = $this->service->indexArticle(99999);

        $this->assertFalse($result);
    }

    public function test_remove_article_sends_delete_request(): void
    {
        Http::fake([
            'localhost:9200/news_articles/_doc/*' => Http::response(['result' => 'deleted'], 200),
        ]);

        $result = $this->service->removeArticle(123);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/news_articles/_doc/123') &&
                   $request->method() === 'DELETE';
        });
    }

    public function test_remove_article_returns_false_on_failure(): void
    {
        Http::fake([
            'localhost:9200/news_articles/_doc/*' => Http::response([], 500),
        ]);

        $result = $this->service->removeArticle(123);

        $this->assertFalse($result);
    }

    public function test_bulk_index_sends_correct_request(): void
    {
        Http::fake([
            'localhost:9200/_bulk' => Http::response(['errors' => false], 200),
        ]);

        $articles = Article::factory()->count(3)->create();
        $ids = $articles->pluck('id')->toArray();

        $result = $this->service->bulkIndex($ids);

        $this->assertEquals(3, $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/_bulk') &&
                   $request->method() === 'POST';
        });
    }

    public function test_bulk_index_returns_zero_for_empty_array(): void
    {
        $result = $this->service->bulkIndex([]);

        $this->assertEquals(0, $result);
    }

    public function test_search_returns_paginated_results(): void
    {
        $articles = Article::factory()->count(3)->create();

        Http::fake([
            'localhost:9200/news_articles/_search' => Http::response([
                'hits' => [
                    'total' => ['value' => 3],
                    'hits' => $articles->map(fn ($a) => ['_id' => $a->id])->toArray(),
                ],
            ], 200),
        ]);

        $filters = new \App\DTOs\ArticleFilterDTO(keyword: 'test');

        $result = $this->service->search($filters);

        $this->assertEquals(3, $result->total());
    }

    public function test_search_with_filters_builds_correct_query(): void
    {
        Http::fake([
            'localhost:9200/news_articles/_search' => Http::response([
                'hits' => [
                    'total' => ['value' => 0],
                    'hits' => [],
                ],
            ], 200),
        ]);

        $filters = new \App\DTOs\ArticleFilterDTO(
            keyword: 'laravel',
            sourceIds: [1, 2],
            categoryIds: [3],
        );

        $this->service->search($filters);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return isset($body['query']['bool']['must']) &&
                   isset($body['query']['bool']['filter']);
        });
    }
}
