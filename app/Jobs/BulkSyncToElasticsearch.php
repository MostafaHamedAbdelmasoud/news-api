<?php

namespace App\Jobs;

use App\Services\ElasticsearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BulkSyncToElasticsearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  int[]  $articleIds
     */
    public function __construct(
        public array $articleIds,
    ) {}

    public function handle(ElasticsearchService $service): void
    {
        if (! $service->isAvailable()) {
            Log::channel('news_api')->warning('Elasticsearch not available for bulk sync');

            return;
        }

        $indexed = $service->bulkIndex($this->articleIds);

        Log::channel('news_api')->info('Bulk sync completed', [
            'requested' => count($this->articleIds),
            'indexed' => $indexed,
        ]);
    }
}
