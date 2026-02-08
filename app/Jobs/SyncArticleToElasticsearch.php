<?php

namespace App\Jobs;

use App\Services\ElasticsearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncArticleToElasticsearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public int $articleId,
        public bool $delete = false,
    ) {}

    public function handle(ElasticsearchService $service): void
    {
        if (! $service->isAvailable()) {
            return;
        }

        if ($this->delete) {
            $service->removeArticle($this->articleId);
        } else {
            $service->indexArticle($this->articleId);
        }
    }
}
