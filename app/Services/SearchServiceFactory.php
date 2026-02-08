<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;

class SearchServiceFactory
{
    public function __construct(
        private ElasticsearchService $elasticsearchService,
        private MySqlSearchService $mySqlSearchService,
    ) {}

    public function make(): SearchServiceInterface
    {
        if ($this->elasticsearchService->isAvailable()) {
            return $this->elasticsearchService;
        }

        return $this->mySqlSearchService;
    }
}
