<?php

namespace App\DTOs;

use DateTimeInterface;

readonly class ArticleDTO
{
    public function __construct(
        public string $title,
        public ?string $content,
        public ?string $summary,
        public string $url,
        public ?string $imageUrl,
        public ?string $authorName,
        public ?string $categorySlug,
        public string $sourceSlug,
        public ?DateTimeInterface $publishedAt,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            content: $data['content'] ?? null,
            summary: $data['summary'] ?? null,
            url: $data['url'],
            imageUrl: $data['image_url'] ?? null,
            authorName: $data['author_name'] ?? null,
            categorySlug: $data['category_slug'] ?? null,
            sourceSlug: $data['source_slug'],
            publishedAt: $data['published_at'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
