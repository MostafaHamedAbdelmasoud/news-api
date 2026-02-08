<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use App\Pipelines\ArticleFilterPipeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MySqlSearchService implements SearchServiceInterface
{
    public function __construct(
        private ArticleFilterPipeline $filterPipeline
    ) {}

    public function search(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['source', 'category', 'author']);

        $query = $this->filterPipeline->apply($query, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function indexArticle(int $articleId): bool
    {
        return true;
    }

    public function removeArticle(int $articleId): bool
    {
        return true;
    }

    public function bulkIndex(array $articleIds): int
    {
        return count($articleIds);
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
