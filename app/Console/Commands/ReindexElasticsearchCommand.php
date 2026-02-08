<?php

namespace App\Console\Commands;

use App\Jobs\BulkSyncToElasticsearch;
use App\Models\Article;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ReindexElasticsearchCommand extends Command
{
    protected $signature = 'elasticsearch:reindex
                            {--chunk=500 : Number of articles to process per chunk}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Reindex all articles in Elasticsearch';

    public function handle(ElasticsearchService $service): int
    {
        if (! config('elasticsearch.enabled')) {
            $this->error('Elasticsearch is not enabled.');

            return self::FAILURE;
        }

        if (! $service->isAvailable()) {
            $this->error('Elasticsearch is not available.');

            return self::FAILURE;
        }

        $chunkSize = (int) $this->option('chunk');
        $sync = $this->option('sync');

        $total = Article::query()->count();
        $this->info("Reindexing {$total} articles...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Article::query()
            ->select('id')
            ->chunk($chunkSize, function ($articles) use ($service, $sync, $bar) {
                $ids = $articles->pluck('id')->toArray();

                if ($sync) {
                    $service->bulkIndex($ids);
                } else {
                    BulkSyncToElasticsearch::dispatch($ids);
                }

                $bar->advance(count($ids));
            });

        $bar->finish();
        $this->newLine();
        $this->info('Reindexing completed!');

        return self::SUCCESS;
    }
}
