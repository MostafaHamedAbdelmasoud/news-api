<?php

namespace App\Contracts;

use App\DTOs\ArticleDTO;

interface NewsAdapterInterface
{
    /**
     * Fetch articles from the news source.
     *
     * @return ArticleDTO[]
     */
    public function fetchArticles(?string $query = null, ?string $category = null): array;

    /**
     * Get the source identifier.
     */
    public function getSourceSlug(): string;

    /**
     * Check if the adapter is enabled.
     */
    public function isEnabled(): bool;
}
