<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ArticleFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArticleSearchRequest;
use App\Http\Resources\ArticleCollection;
use App\Services\ArticleService;

/**
 * @group Search
 *
 * APIs for searching articles.
 * Search is powered by Elasticsearch (if enabled) or MySQL FULLTEXT search.
 */
class SearchController extends Controller
{
    public function __construct(
        private ArticleService $articleService
    ) {}

    /**
     * Search articles
     *
     * Search for articles using full-text search.
     * Uses Elasticsearch when available, falls back to MySQL FULLTEXT search.
     *
     * @unauthenticated
     *
     * @queryParam keyword string required The search keyword (minimum 2 characters). Example: artificial intelligence
     * @queryParam date_from date Filter articles published on or after this date. Format: YYYY-MM-DD. Example: 2024-01-01
     * @queryParam date_to date Filter articles published on or before this date. Format: YYYY-MM-DD. Example: 2024-12-31
     * @queryParam source_ids integer[] Filter by source IDs. Example: [1, 2]
     * @queryParam category_ids integer[] Filter by category IDs. Example: [1, 3]
     * @queryParam author_ids integer[] Filter by author IDs. Example: [5, 10]
     * @queryParam sort_by string Sort field. Allowed: published_at, created_at, title. Example: published_at
     * @queryParam sort_direction string Sort direction. Allowed: asc, desc. Example: desc
     * @queryParam per_page integer Number of articles per page (max 100). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Search results found" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Advances in Artificial Intelligence",
     *       "slug": "advances-in-artificial-intelligence",
     *       "excerpt": "Recent breakthroughs in AI technology...",
     *       "content": "Full article content here...",
     *       "url": "https://example.com/article",
     *       "image_url": "https://example.com/image.jpg",
     *       "published_at": "2024-01-15T10:30:00Z",
     *       "source": {
     *         "id": 1,
     *         "name": "NewsAPI",
     *         "slug": "newsapi"
     *       },
     *       "category": {
     *         "id": 1,
     *         "name": "Technology",
     *         "slug": "technology"
     *       },
     *       "author": {
     *         "id": 1,
     *         "name": "Jane Smith"
     *       }
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/articles/search?keyword=artificial+intelligence&page=1",
     *     "last": "http://localhost/api/v1/articles/search?keyword=artificial+intelligence&page=5",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/articles/search?keyword=artificial+intelligence&page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 75
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "Search keyword is required.",
     *   "errors": {
     *     "keyword": ["Search keyword is required."]
     *   }
     * }
     */
    public function search(ArticleSearchRequest $request): ArticleCollection
    {
        $filters = ArticleFilterDTO::fromRequest($request->validated());
        $articles = $this->articleService->search($filters);

        return new ArticleCollection($articles);
    }
}
