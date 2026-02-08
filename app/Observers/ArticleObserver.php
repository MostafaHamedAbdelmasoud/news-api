<?php

namespace App\Observers;

use App\Jobs\SyncArticleToElasticsearch;
use App\Models\Article;

class ArticleObserver
{
    public function created(Article $article): void
    {
        if (config('elasticsearch.enabled')) {
            SyncArticleToElasticsearch::dispatch($article->id);
        }
    }

    public function updated(Article $article): void
    {
        if (config('elasticsearch.enabled')) {
            SyncArticleToElasticsearch::dispatch($article->id);
        }
    }

    public function deleted(Article $article): void
    {
        if (config('elasticsearch.enabled')) {
            SyncArticleToElasticsearch::dispatch($article->id, delete: true);
        }
    }

    public function forceDeleted(Article $article): void
    {
        if (config('elasticsearch.enabled')) {
            SyncArticleToElasticsearch::dispatch($article->id, delete: true);
        }
    }

    public function restored(Article $article): void
    {
        if (config('elasticsearch.enabled')) {
            SyncArticleToElasticsearch::dispatch($article->id);
        }
    }
}
