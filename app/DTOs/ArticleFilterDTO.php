<?php

namespace App\DTOs;

use DateTimeInterface;

readonly class ArticleFilterDTO
{
    public function __construct(
        public ?string $keyword = null,
        public ?DateTimeInterface $dateFrom = null,
        public ?DateTimeInterface $dateTo = null,
        public ?array $sourceIds = null,
        public ?array $categoryIds = null,
        public ?array $authorIds = null,
        public string $sortBy = 'published_at',
        public string $sortDirection = 'desc',
        public int $perPage = 15,
        public int $page = 1,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            keyword: $data['keyword'] ?? null,
            dateFrom: isset($data['date_from']) ? new \DateTime($data['date_from']) : null,
            dateTo: isset($data['date_to']) ? new \DateTime($data['date_to']) : null,
            sourceIds: $data['source_ids'] ?? null,
            categoryIds: $data['category_ids'] ?? null,
            authorIds: $data['author_ids'] ?? null,
            sortBy: $data['sort_by'] ?? 'published_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            perPage: min((int) ($data['per_page'] ?? 15), 100),
            page: max((int) ($data['page'] ?? 1), 1),
        );
    }
}
