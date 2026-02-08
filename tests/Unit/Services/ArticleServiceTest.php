<?php

namespace Tests\Unit\Services;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\SearchServiceInterface;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use App\Services\ArticleService;
use App\Services\SearchServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    private ArticleRepositoryInterface $repository;

    private SearchServiceFactory $searchFactory;

    private ArticleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ArticleRepositoryInterface::class);
        $this->searchFactory = Mockery::mock(SearchServiceFactory::class);

        $this->service = new ArticleService($this->repository, $this->searchFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_paginated_delegates_to_repository(): void
    {
        $filters = new ArticleFilterDTO;
        $expectedResult = new LengthAwarePaginator([], 0, 15);

        $this->repository
            ->shouldReceive('getPaginated')
            ->once()
            ->with($filters)
            ->andReturn($expectedResult);

        $result = $this->service->getPaginated($filters);

        $this->assertSame($expectedResult, $result);
    }

    public function test_find_by_id_delegates_to_repository(): void
    {
        $article = Article::factory()->make(['id' => 1]);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($article);

        $result = $this->service->findById(1);

        $this->assertSame($article, $result);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(99999)
            ->andReturnNull();

        $result = $this->service->findById(99999);

        $this->assertNull($result);
    }

    public function test_search_uses_search_service_from_factory(): void
    {
        $filters = new ArticleFilterDTO(keyword: 'test');
        $expectedResult = new LengthAwarePaginator([], 0, 15);

        $searchService = Mockery::mock(SearchServiceInterface::class);
        $searchService
            ->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn($expectedResult);

        $this->searchFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($searchService);

        $result = $this->service->search($filters);

        $this->assertSame($expectedResult, $result);
    }

    public function test_get_personalized_feed_delegates_to_repository(): void
    {
        $userId = 1;
        $perPage = 20;
        $expectedResult = new LengthAwarePaginator([], 0, $perPage);

        $this->repository
            ->shouldReceive('getPersonalizedFeed')
            ->once()
            ->with($userId, $perPage)
            ->andReturn($expectedResult);

        $result = $this->service->getPersonalizedFeed($userId, $perPage);

        $this->assertSame($expectedResult, $result);
    }

    public function test_get_personalized_feed_uses_default_per_page(): void
    {
        $userId = 1;
        $expectedResult = new LengthAwarePaginator([], 0, 15);

        $this->repository
            ->shouldReceive('getPersonalizedFeed')
            ->once()
            ->with($userId, 15)
            ->andReturn($expectedResult);

        $result = $this->service->getPersonalizedFeed($userId);

        $this->assertSame($expectedResult, $result);
    }
}
