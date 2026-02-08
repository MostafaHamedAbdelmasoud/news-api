<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Services\SourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Sources
 *
 * APIs for retrieving news sources.
 * Sources represent the news providers from which articles are aggregated (e.g., NewsAPI, The Guardian, NYTimes).
 */
class SourceController extends Controller
{
    public function __construct(
        private SourceService $sourceService
    ) {}

    /**
     * List all sources
     *
     * Retrieve a list of all active news sources.
     *
     * @unauthenticated
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "NewsAPI",
     *       "slug": "newsapi",
     *       "url": "https://newsapi.org",
     *       "description": "NewsAPI is a simple HTTP REST API for searching and retrieving live articles from all over the web.",
     *       "is_active": true
     *     },
     *     {
     *       "id": 2,
     *       "name": "The Guardian",
     *       "slug": "the-guardian",
     *       "url": "https://www.theguardian.com",
     *       "description": "The Guardian is a British daily newspaper.",
     *       "is_active": true
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $sources = $this->sourceService->getActive();

        return SourceResource::collection($sources);
    }

    /**
     * Get source details
     *
     * Retrieve details of a specific news source by its ID.
     *
     * @unauthenticated
     *
     * @urlParam id integer required The ID of the source. Example: 1
     *
     * @response 200 scenario="Source found" {
     *   "data": {
     *     "id": 1,
     *     "name": "NewsAPI",
     *     "slug": "newsapi",
     *     "url": "https://newsapi.org",
     *     "description": "NewsAPI is a simple HTTP REST API for searching and retrieving live articles from all over the web.",
     *     "is_active": true
     *   }
     * }
     * @response 404 scenario="Source not found" {
     *   "message": "Source not found"
     * }
     */
    public function show(int $id): SourceResource|JsonResponse
    {
        $source = $this->sourceService->findById($id);

        if (! $source) {
            return response()->json(['message' => 'Source not found'], 404);
        }

        return new SourceResource($source);
    }
}
