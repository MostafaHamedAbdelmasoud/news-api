<?php

namespace App\Contracts;

use App\DTOs\ArticleFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchServiceInterface
{
    /**
     * Search articles by keyword and optional filters.
     */
    public function search(ArticleFilterDTO $filters): LengthAwarePaginator;

    /**
     * Index an article for search.
     */
    public function indexArticle(int $articleId): bool;

    /**
     * Remove an article from the search index.
     */
    public function removeArticle(int $articleId): bool;

    /**
     * Bulk index multiple articles.
     *
     * @param  int[]  $articleIds
     */
    public function bulkIndex(array $articleIds): int;

    /**
     * Check if the search service is available.
     */
    public function isAvailable(): bool;
}
