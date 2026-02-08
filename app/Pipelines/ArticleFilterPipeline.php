<?php

namespace App\Pipelines;

use App\DTOs\ArticleFilterDTO;
use App\Pipelines\Pipes\AuthorFilter;
use App\Pipelines\Pipes\CategoryFilter;
use App\Pipelines\Pipes\DateRangeFilter;
use App\Pipelines\Pipes\KeywordFilter;
use App\Pipelines\Pipes\SortFilter;
use App\Pipelines\Pipes\SourceFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

class ArticleFilterPipeline
{
    /**
     * @var array<class-string>
     */
    protected array $pipes = [
        KeywordFilter::class,
        DateRangeFilter::class,
        CategoryFilter::class,
        SourceFilter::class,
        AuthorFilter::class,
        SortFilter::class,
    ];

    public function apply(Builder $query, ArticleFilterDTO $filters): Builder
    {
        return app(Pipeline::class)
            ->send(['query' => $query, 'filters' => $filters])
            ->through($this->pipes)
            ->then(fn ($data) => $data['query']);
    }
}
