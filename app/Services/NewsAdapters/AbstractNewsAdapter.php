<?php

namespace App\Services\NewsAdapters;

use App\Contracts\NewsAdapterInterface;
use App\DTOs\ArticleDTO;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractNewsAdapter implements NewsAdapterInterface
{
    protected string $sourceSlug;

    protected array $config;

    public function __construct()
    {
        $this->config = config("news_sources.sources.{$this->sourceSlug}", []);
    }

    public function getSourceSlug(): string
    {
        return $this->sourceSlug;
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    protected function getApiKey(): ?string
    {
        return $this->config['api_key'] ?? null;
    }

    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? '';
    }

    protected function getTimeout(): int
    {
        return $this->config['timeout'] ?? config('news_sources.default_timeout', 30);
    }

    protected function httpClient(): PendingRequest
    {
        return Http::timeout($this->getTimeout())
            ->retry(3, 100);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('news_api')->info("[{$this->sourceSlug}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('news_api')->error("[{$this->sourceSlug}] {$message}", $context);
    }

    /**
     * Transform raw API response to array of ArticleDTO.
     *
     * @return ArticleDTO[]
     */
    abstract protected function transformResponse(array $data): array;

    /**
     * Make the API request and return raw response.
     */
    abstract protected function makeRequest(?string $query, ?string $category): array;

    /**
     * @return ArticleDTO[]
     */
    public function fetchArticles(?string $query = null, ?string $category = null): array
    {
        if (! $this->isEnabled()) {
            $this->logInfo('Adapter is disabled, skipping fetch');

            return [];
        }

        if (! $this->getApiKey()) {
            $this->logError('API key is not configured');

            return [];
        }

        try {
            $this->logInfo('Starting fetch', ['query' => $query, 'category' => $category]);
            $data = $this->makeRequest($query, $category);
            $articles = $this->transformResponse($data);
            $this->logInfo('Fetch completed', ['count' => count($articles)]);

            return $articles;
        } catch (\Throwable $e) {
            $this->logError('Fetch failed', [
                'error' => $e->getMessage(),
                'query' => $query,
                'category' => $category,
            ]);

            return [];
        }
    }

    protected function mapCategory(?string $externalCategory): ?string
    {
        if (! $externalCategory) {
            return null;
        }

        $mapping = config("news_sources.category_mapping.{$this->sourceSlug}", []);

        return $mapping[strtolower($externalCategory)] ?? 'general';
    }
}
