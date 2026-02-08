<?php

namespace App\DTOs;

use App\Enums\FetchStatus;

readonly class FetchResultDTO
{
    public function __construct(
        public FetchStatus $status,
        public int $articlesFetched,
        public int $articlesCreated,
        public int $articlesUpdated,
        public ?string $errorMessage = null,
    ) {}

    public static function success(int $fetched, int $created, int $updated): self
    {
        return new self(
            status: FetchStatus::Success,
            articlesFetched: $fetched,
            articlesCreated: $created,
            articlesUpdated: $updated,
        );
    }

    public static function failed(string $error): self
    {
        return new self(
            status: FetchStatus::Failed,
            articlesFetched: 0,
            articlesCreated: 0,
            articlesUpdated: 0,
            errorMessage: $error,
        );
    }

    public static function partial(int $fetched, int $created, int $updated, string $error): self
    {
        return new self(
            status: FetchStatus::Partial,
            articlesFetched: $fetched,
            articlesCreated: $created,
            articlesUpdated: $updated,
            errorMessage: $error,
        );
    }
}
