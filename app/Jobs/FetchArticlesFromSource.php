<?php

namespace App\Jobs;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\NewsAdapterInterface;
use App\DTOs\FetchResultDTO;
use App\Models\ApiFetchLog;
use App\Services\NewsAdapters\GuardianAdapter;
use App\Services\NewsAdapters\NewsApiAdapter;
use App\Services\NewsAdapters\NytimesAdapter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchArticlesFromSource implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $sourceSlug,
        public ?string $query = null,
        public ?string $category = null,
    ) {}

    public function handle(ArticleRepositoryInterface $repository): void
    {
        $adapter = $this->resolveAdapter();

        if (! $adapter) {
            $this->logFetch(FetchResultDTO::failed("Adapter not found for source: {$this->sourceSlug}"));

            return;
        }

        if (! $adapter->isEnabled()) {
            Log::channel('news_api')->info("Adapter {$this->sourceSlug} is disabled, skipping fetch");

            return;
        }

        try {
            $articles = $adapter->fetchArticles($this->query, $this->category);
            $created = 0;
            $updated = 0;

            foreach ($articles as $dto) {
                $result = $repository->upsertFromDto($dto);
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $this->logFetch(FetchResultDTO::success(count($articles), $created, $updated));
        } catch (\Throwable $e) {

            Log::channel('news_api')->error("Fetch failed for {$this->sourceSlug}", [
                'error' => $e->getMessage(),
            ]);
            $this->logFetch(FetchResultDTO::failed($e->getMessage()));

            throw $e;
        }
    }

    private function resolveAdapter(): ?NewsAdapterInterface
    {
        $adapterClass = match ($this->sourceSlug) {
            'newsapi' => NewsApiAdapter::class,
            'guardian' => GuardianAdapter::class,
            'nytimes' => NytimesAdapter::class,
            default => null,
        };

        if (! $adapterClass) {
            return null;
        }

        return app($adapterClass);
    }

    private function logFetch(FetchResultDTO $result): void
    {
        ApiFetchLog::create([
            'source' => $this->sourceSlug,
            'status' => $result->status->value,
            'articles_fetched' => $result->articlesFetched,
            'articles_created' => $result->articlesCreated,
            'articles_updated' => $result->articlesUpdated,
            'error_message' => $result->errorMessage,
            'fetched_at' => now(),
        ]);
    }
}
