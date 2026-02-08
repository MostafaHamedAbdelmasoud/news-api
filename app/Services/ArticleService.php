<?php

namespace App\Services;

use App\Contracts\ArticleRepositoryInterface;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private SearchServiceFactory $searchFactory,
    ) {}

    public function getPaginated(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters);
    }

    public function findById(int $id): ?Article
    {
        return $this->repository->findById($id);
    }

    public function search(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        $searchService = $this->searchFactory->make();

        return $searchService->search($filters);
    }

    public function getPersonalizedFeed(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPersonalizedFeed($userId, $perPage);
    }
}
