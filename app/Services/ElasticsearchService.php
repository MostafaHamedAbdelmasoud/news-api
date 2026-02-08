<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElasticsearchService implements SearchServiceInterface
{
    private string $host;

    private int $port;

    private string $scheme;

    private string $indexName;

    public function __construct()
    {
        $config = config('elasticsearch');
        $this->host = $config['hosts'][0]['host'] ?? 'localhost';
        $this->port = $config['hosts'][0]['port'] ?? 9200;
        $this->scheme = $config['hosts'][0]['scheme'] ?? 'http';
        $this->indexName = $config['indices']['articles']['name'] ?? 'news_articles';
    }

    public function search(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        try {
            $body = $this->buildSearchQuery($filters);
            $response = Http::post($this->getUrl("/{$this->indexName}/_search"), $body);

            if (! $response->successful()) {
                throw new \RuntimeException('Elasticsearch search failed');
            }

            return $this->hydrateResults($response->json(), $filters);
        } catch (\Throwable $e) {
            Log::channel('news_api')->error('Elasticsearch search failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function buildSearchQuery(ArticleFilterDTO $filters): array
    {
        return [
            'query' => [
                'bool' => [
                    'must' => $this->buildMustClause($filters),
                    'filter' => $this->buildFilterClauses($filters),
                ],
            ],
            'sort' => [
                [$filters->sortBy => ['order' => $filters->sortDirection]],
            ],
            'from' => ($filters->page - 1) * $filters->perPage,
            'size' => $filters->perPage,
        ];
    }

    private function buildMustClause(ArticleFilterDTO $filters): array
    {
        if (! $filters->keyword) {
            return [['match_all' => new \stdClass]];
        }

        return [
            [
                'multi_match' => [
                    'query' => $filters->keyword,
                    'fields' => ['title^3', 'content', 'summary^2'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];
    }

    private function buildFilterClauses(ArticleFilterDTO $filters): array
    {
        $clauses = [];

        if ($filters->sourceIds) {
            $clauses[] = ['terms' => ['source_id' => $filters->sourceIds]];
        }

        if ($filters->categoryIds) {
            $clauses[] = ['terms' => ['category_id' => $filters->categoryIds]];
        }

        if ($filters->authorIds) {
            $clauses[] = ['terms' => ['author_id' => $filters->authorIds]];
        }

        if ($filters->dateFrom || $filters->dateTo) {
            $clauses[] = ['range' => ['published_at' => $this->buildDateRange($filters)]];
        }

        return $clauses;
    }

    private function buildDateRange(ArticleFilterDTO $filters): array
    {
        $range = [];

        if ($filters->dateFrom) {
            $range['gte'] = $filters->dateFrom->format('Y-m-d');
        }

        if ($filters->dateTo) {
            $range['lte'] = $filters->dateTo->format('Y-m-d');
        }

        return $range;
    }

    private function hydrateResults(array $response, ArticleFilterDTO $filters): LengthAwarePaginator
    {
        $total = $response['hits']['total']['value'] ?? 0;
        $ids = array_map(fn ($hit) => $hit['_id'], $response['hits']['hits'] ?? []);

        if (empty($ids)) {
            return new Paginator(collect(), $total, $filters->perPage, $filters->page);
        }

        $articles = Article::query()
            ->with(['source', 'category', 'author'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($article) => array_search($article->id, $ids));

        return new Paginator($articles->values(), $total, $filters->perPage, $filters->page);
    }

    public function indexArticle(int $articleId): bool
    {
        $article = Article::find($articleId);

        if (! $article) {
            return false;
        }

        $document = [
            'title' => $article->title,
            'content' => $article->content,
            'summary' => $article->summary,
            'url' => $article->url,
            'image_url' => $article->image_url,
            'source_id' => $article->source_id,
            'category_id' => $article->category_id,
            'author_id' => $article->author_id,
            'published_at' => $article->published_at?->toIso8601String(),
        ];

        try {
            $response = Http::put(
                $this->getUrl("/{$this->indexName}/_doc/{$articleId}"),
                $document
            );

            return $response->successful();
        } catch (\Throwable $e) {
            Log::channel('news_api')->error('Failed to index article', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function removeArticle(int $articleId): bool
    {
        try {
            $response = Http::delete(
                $this->getUrl("/{$this->indexName}/_doc/{$articleId}")
            );

            return $response->successful();
        } catch (\Throwable $e) {
            Log::channel('news_api')->error('Failed to remove article from index', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function bulkIndex(array $articleIds): int
    {
        $articles = Article::query()->whereIn('id', $articleIds)->get();
        $indexed = 0;

        $body = '';
        foreach ($articles as $article) {
            $body .= json_encode(['index' => ['_index' => $this->indexName, '_id' => $article->id]])."\n";
            $body .= json_encode([
                'title' => $article->title,
                'content' => $article->content,
                'summary' => $article->summary,
                'url' => $article->url,
                'image_url' => $article->image_url,
                'source_id' => $article->source_id,
                'category_id' => $article->category_id,
                'author_id' => $article->author_id,
                'published_at' => $article->published_at?->toIso8601String(),
            ])."\n";
            $indexed++;
        }

        if (empty($body)) {
            return 0;
        }

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/x-ndjson'])
                ->withBody($body, 'application/x-ndjson')
                ->post($this->getUrl('/_bulk'));

            if (! $response->successful()) {
                return 0;
            }

            return $indexed;
        } catch (\Throwable $e) {
            Log::channel('news_api')->error('Bulk indexing failed', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function isAvailable(): bool
    {
        if (! config('elasticsearch.enabled')) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get($this->getUrl('/'));

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function getUrl(string $path = ''): string
    {
        return "{$this->scheme}://{$this->host}:{$this->port}{$path}";
    }
}
