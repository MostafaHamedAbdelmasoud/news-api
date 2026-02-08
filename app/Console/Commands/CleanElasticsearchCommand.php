<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class CleanElasticsearchCommand extends Command
{
    protected $signature = 'elasticsearch:clean
                            {--dry-run : Show what would be removed without actually removing}';

    protected $description = 'Remove soft-deleted articles from Elasticsearch index';

    public function handle(ElasticsearchService $service): int
    {
        if (! $service->isAvailable()) {
            $this->error('Elasticsearch is not available.');

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        $deletedArticles = Article::onlyTrashed()->pluck('id');

        if ($deletedArticles->isEmpty()) {
            $this->info('No soft-deleted articles found.');

            return self::SUCCESS;
        }

        $this->info("Found {$deletedArticles->count()} soft-deleted articles.");

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->table(['Article ID'], $deletedArticles->map(fn ($id) => [$id])->toArray());

            return self::SUCCESS;
        }

        $removed = 0;
        $failed = 0;

        $this->withProgressBar($deletedArticles, function ($articleId) use ($service, &$removed, &$failed) {
            if ($service->removeArticle($articleId)) {
                $removed++;
            } else {
                $failed++;
            }
        });

        $this->newLine(2);
        $this->info("Removed: {$removed}");

        if ($failed > 0) {
            $this->warn("Failed: {$failed} (may not exist in index)");
        }

        return self::SUCCESS;
    }
}
