<?php

namespace App\Contracts;

use App\DTOs\ArticleDTO;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    /**
     * Get paginated articles with optional filters.
     */
    public function getPaginated(ArticleFilterDTO $filters): LengthAwarePaginator;

    /**
     * Find article by ID.
     */
    public function findById(int $id): ?Article;

    /**
     * Create or update an article from DTO.
     *
     * @return array{article: Article, created: bool}
     */
    public function upsertFromDto(ArticleDTO $dto): array;

    /**
     * Get personalized feed for a user.
     */
    public function getPersonalizedFeed(int $userId, int $perPage = 15): LengthAwarePaginator;
}
