<?php

namespace App\Repositories;

use App\Contracts\ArticleRepositoryInterface;
use App\DTOs\ArticleDTO;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CachedArticleRepository implements ArticleRepositoryInterface
{
    private const CACHE_TTL = 300;

    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function getPaginated(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        $cacheKey = $this->buildCacheKey('articles.paginated', $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            return $this->repository->getPaginated($filters);
        });
    }

    public function findById(int $id): ?Article
    {
        $cacheKey = "articles.{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->repository->findById($id);
        });
    }

    /**
     * @return array{article: Article, created: bool}
     */
    public function upsertFromDto(ArticleDTO $dto): array
    {
        $result = $this->repository->upsertFromDto($dto);

        $this->clearArticleCache();

        return $result;
    }

    public function getPersonalizedFeed(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "user.{$userId}.feed.{$perPage}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $perPage) {
            return $this->repository->getPersonalizedFeed($userId, $perPage);
        });
    }

    private function buildCacheKey(string $prefix, ArticleFilterDTO $filters): string
    {
        $params = [
            $filters->keyword,
            $filters->dateFrom?->format('Y-m-d'),
            $filters->dateTo?->format('Y-m-d'),
            implode(',', $filters->sourceIds ?? []),
            implode(',', $filters->categoryIds ?? []),
            implode(',', $filters->authorIds ?? []),
            $filters->sortBy,
            $filters->sortDirection,
            $filters->perPage,
            $filters->page,
        ];

        return $prefix.'.'.md5(implode('|', $params));
    }

    private function clearArticleCache(): void
    {
        Cache::forget('articles.paginated.*');
    }
}
