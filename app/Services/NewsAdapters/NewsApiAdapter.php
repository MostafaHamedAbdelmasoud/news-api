<?php

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Carbon\Carbon;

class NewsApiAdapter extends AbstractNewsAdapter
{
    protected string $sourceSlug = 'newsapi';

    protected function makeRequest(?string $query, ?string $category): array
    {
        $endpoint = $this->getBaseUrl().($category ? '/top-headlines' : '/everything');

        $params = [
            'apiKey' => $this->getApiKey(),
            'pageSize' => config('news_sources.max_articles_per_fetch', 100),
            'language' => 'en',
        ];

        if ($query) {
            $params['q'] = $query;
        }

        if ($category) {
            $params['category'] = $category;
        }

        if (! $query && ! $category) {
            $params['q'] = 'news';
        }

        $response = $this->httpClient()->get($endpoint, $params);

        if (! $response->successful()) {
            throw new \RuntimeException("NewsAPI request failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * @return ArticleDTO[]
     */
    protected function transformResponse(array $data): array
    {
        if (($data['status'] ?? '') !== 'ok') {
            $this->logError('Invalid response status', ['response' => $data]);

            return [];
        }

        $articles = [];

        foreach ($data['articles'] ?? [] as $item) {
            if (empty($item['url']) || empty($item['title'])) {
                continue;
            }

            $articles[] = new ArticleDTO(
                title: $item['title'],
                content: $item['content'] ?? null,
                summary: $item['description'] ?? null,
                url: $item['url'],
                imageUrl: $item['urlToImage'] ?? null,
                authorName: $item['author'] ?? null,
                categorySlug: $this->mapCategory($item['source']['category'] ?? null),
                sourceSlug: $this->sourceSlug,
                publishedAt: isset($item['publishedAt']) ? Carbon::parse($item['publishedAt']) : null,
                metadata: [
                    'original_source' => $item['source']['name'] ?? null,
                    'original_source_id' => $item['source']['id'] ?? null,
                ],
            );
        }

        return $articles;
    }
}
