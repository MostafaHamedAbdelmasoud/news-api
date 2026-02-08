<?php

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Carbon\Carbon;

class NytimesAdapter extends AbstractNewsAdapter
{
    protected string $sourceSlug = 'nytimes';

    protected function makeRequest(?string $query, ?string $category): array
    {
        $endpoint = $this->getBaseUrl().'/search/v2/articlesearch.json';

        $params = [
            'api-key' => $this->getApiKey(),
            'sort' => 'newest',
        ];

        if ($query) {
            $params['q'] = $query;
        } else {
            $params['q'] = 'news';
        }

        if ($category) {
            $params['fq'] = "section_name:(\"{$category}\")";
        }

        $response = $this->httpClient()->get($endpoint, $params);

        if (! $response->successful()) {
            throw new \RuntimeException("NYTimes API request failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * @return ArticleDTO[]
     */
    protected function transformResponse(array $data): array
    {
        if (($data['status'] ?? '') !== 'OK') {
            $this->logError('Invalid response status', ['response' => $data]);

            return [];
        }

        $articles = [];

        foreach ($data['response']['docs'] ?? [] as $item) {
            if (empty($item['web_url']) || empty($item['headline']['main'])) {
                continue;
            }

            $imageUrl = null;
            if (! empty($item['multimedia'])) {
                foreach ($item['multimedia'] as $media) {
                    if ($media['type'] === 'image') {
                        $imageUrl = 'https://www.nytimes.com/'.$media['url'];
                        break;
                    }
                }
            }

            $authorName = null;
            if (! empty($item['byline']['original'])) {
                $authorName = preg_replace('/^By\s+/i', '', $item['byline']['original']);
            }

            $articles[] = new ArticleDTO(
                title: $item['headline']['main'],
                content: $item['lead_paragraph'] ?? null,
                summary: $item['abstract'] ?? null,
                url: $item['web_url'],
                imageUrl: $imageUrl,
                authorName: $authorName,
                categorySlug: $this->mapCategory($item['section_name'] ?? null),
                sourceSlug: $this->sourceSlug,
                publishedAt: isset($item['pub_date']) ? Carbon::parse($item['pub_date']) : null,
                metadata: [
                    'document_type' => $item['document_type'] ?? null,
                    'news_desk' => $item['news_desk'] ?? null,
                    'section_name' => $item['section_name'] ?? null,
                    'subsection_name' => $item['subsection_name'] ?? null,
                ],
            );
        }

        return $articles;
    }
}
