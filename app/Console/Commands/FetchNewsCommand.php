<?php

namespace App\Console\Commands;

use App\Enums\NewsSource;
use App\Jobs\FetchArticlesFromSource;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch
                            {--source= : Specific source to fetch from (newsapi, guardian, nytimes)}
                            {--all : Fetch from all sources}
                            {--query= : Search query}
                            {--category= : Category to filter}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Fetch articles from news sources';

    public function handle(): int
    {
        $source = $this->option('source');
        $all = $this->option('all');
        $query = $this->option('query');
        $category = $this->option('category');
        $sync = $this->option('sync');

        if (! $source && ! $all) {
            $this->error('Please specify --source or --all');

            return self::FAILURE;
        }

        $sources = $all
            ? array_column(NewsSource::cases(), 'value')
            : [$source];

        foreach ($sources as $sourceSlug) {
            $this->info("Fetching articles from {$sourceSlug}...");

            $job = new FetchArticlesFromSource($sourceSlug, $query, $category);

            if ($sync) {
                $job->handle(app(\App\Contracts\ArticleRepositoryInterface::class));
            } else {
                dispatch($job);
            }
        }

        $this->info('Fetch jobs dispatched successfully!');

        return self::SUCCESS;
    }
}
