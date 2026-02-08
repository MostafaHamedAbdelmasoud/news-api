<?php

namespace App\Repositories;

use App\Contracts\ArticleRepositoryInterface;
use App\DTOs\ArticleDTO;
use App\DTOs\ArticleFilterDTO;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\UserPreference;
use App\Pipelines\ArticleFilterPipeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private ArticleFilterPipeline $filterPipeline
    ) {}

    public function getPaginated(ArticleFilterDTO $filters): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['source', 'category', 'author']);

        $query = $this->filterPipeline->apply($query, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function findById(int $id): ?Article
    {
        return Article::query()
            ->with(['source', 'category', 'author'])
            ->find($id);
    }

    /**
     * @return array{article: Article, created: bool}
     */
    public function upsertFromDto(ArticleDTO $dto): array
    {
        $sourceId = $this->resolveSourceId($dto->sourceSlug);
        $categoryId = $this->resolveCategoryId($dto->categorySlug);
        $authorId = $this->resolveAuthorId($dto->authorName, $sourceId);

        $article = Article::query()->where('url', $dto->url)->first();
        $created = $article === null;

        if ($created) {
            $article = new Article;
            $article->url = $dto->url;
        }

        $article->title = $dto->title;
        $article->content = $dto->content;
        $article->summary = $dto->summary;
        $article->image_url = $dto->imageUrl;
        $article->source_id = $sourceId;
        $article->category_id = $categoryId;
        $article->author_id = $authorId;
        $article->published_at = $dto->publishedAt;
        $article->metadata = $dto->metadata;
        $article->save();

        return ['article' => $article, 'created' => $created];
    }

    public function getPersonalizedFeed(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $preference = UserPreference::query()
            ->where('user_id', $userId)
            ->first();

        $query = Article::query()
            ->with(['source', 'category', 'author'])
            ->published();

        if ($preference) {
            $query->where(function ($q) use ($preference) {
                if (! empty($preference->preferred_sources)) {
                    $q->orWhereIn('source_id', $preference->preferred_sources);
                }
                if (! empty($preference->preferred_categories)) {
                    $q->orWhereIn('category_id', $preference->preferred_categories);
                }
                if (! empty($preference->preferred_authors)) {
                    $q->orWhereIn('author_id', $preference->preferred_authors);
                }
            });
        }

        return $query
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    private function resolveSourceId(string $slug): ?int
    {
        return Source::query()
            ->where('slug', $slug)
            ->value('id');
    }

    private function resolveCategoryId(?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }

        return Category::query()
            ->where('slug', $slug)
            ->value('id');
    }

    private function resolveAuthorId(?string $name, ?int $sourceId): ?int
    {
        if (! $name) {
            return null;
        }

        return Author::query()->firstOrCreate(
            ['name' => $name, 'source_id' => $sourceId],
            ['name' => $name, 'source_id' => $sourceId]
        )->id;
    }
}
