<?php

namespace App\Providers;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\SearchServiceInterface;
use App\Models\Article;
use App\Observers\ArticleObserver;
use App\Pipelines\ArticleFilterPipeline;
use App\Repositories\CachedArticleRepository;
use App\Repositories\EloquentArticleRepository;
use App\Services\SearchServiceFactory;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArticleFilterPipeline::class);

        $this->app->singleton(EloquentArticleRepository::class, function ($app) {
            return new EloquentArticleRepository(
                $app->make(ArticleFilterPipeline::class)
            );
        });

        $this->app->singleton(ArticleRepositoryInterface::class, function ($app) {
            $eloquentRepository = $app->make(EloquentArticleRepository::class);

            if (config('cache.default') !== 'null') {
                return new CachedArticleRepository($eloquentRepository);
            }

            return $eloquentRepository;
        });

        $this->app->singleton(SearchServiceInterface::class, function ($app) {
            return $app->make(SearchServiceFactory::class)->make();
        });
    }

    public function boot(): void
    {
        Article::observe(ArticleObserver::class);
    }
}
