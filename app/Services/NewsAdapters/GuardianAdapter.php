<?php

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Carbon\Carbon;

class GuardianAdapter extends AbstractNewsAdapter
{
    protected string $sourceSlug = 'guardian';

    protected function makeRequest(?string $query, ?string $category): array
    {
        $endpoint = $this->getBaseUrl().'/search';

        $params = [
            'api-key' => $this->getApiKey(),
            'page-size' => min(config('news_sources.max_articles_per_fetch', 100), 200),
            'show-fields' => 'headline,body,trailText,thumbnail,byline',
            'order-by' => 'newest',
        ];

        if ($query) {
            $params['q'] = $query;
        }

        if ($category) {
            $params['section'] = $category;
        }

        $response = $this->httpClient()->get($endpoint, $params);

        if (! $response->successful()) {
            throw new \RuntimeException("Guardian API request failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * @return ArticleDTO[]
     */
    protected function transformResponse(array $data): array
    {
        if (($data['response']['status'] ?? '') !== 'ok') {
            $this->logError('Invalid response status', ['response' => $data]);

            return [];
        }

        $articles = [];

        foreach ($data['response']['results'] ?? [] as $item) {
            if (empty($item['webUrl']) || empty($item['webTitle'])) {
                continue;
            }

            $fields = $item['fields'] ?? [];

            $articles[] = new ArticleDTO(
                title: $item['webTitle'],
                content: $fields['body'] ?? null,
                summary: $fields['trailText'] ?? null,
                url: $item['webUrl'],
                imageUrl: $fields['thumbnail'] ?? null,
                authorName: $fields['byline'] ?? null,
                categorySlug: $this->mapCategory($item['sectionId'] ?? null),
                sourceSlug: $this->sourceSlug,
                publishedAt: isset($item['webPublicationDate']) ? Carbon::parse($item['webPublicationDate']) : null,
                metadata: [
                    'section_id' => $item['sectionId'] ?? null,
                    'section_name' => $item['sectionName'] ?? null,
                    'pillar_id' => $item['pillarId'] ?? null,
                    'pillar_name' => $item['pillarName'] ?? null,
                ],
            );

        }

        return $articles;
    }
}
