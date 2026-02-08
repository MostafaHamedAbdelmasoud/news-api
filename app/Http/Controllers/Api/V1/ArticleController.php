<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ArticleFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArticleIndexRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;

/**
 * @group Articles
 *
 * APIs for browsing and retrieving articles.
 * Articles are aggregated from multiple news sources including NewsAPI, The Guardian, and New York Times.
 */
class ArticleController extends Controller
{
    public function __construct(
        private ArticleService $articleService
    ) {}

    /**
     * List articles
     *
     * Retrieve a paginated list of articles with optional filters.
     * Supports filtering by keyword, date range, sources, categories, and authors.
     *
     * @unauthenticated
     *
     * @queryParam keyword string Filter articles by keyword in title or content. Example: technology
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
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Breaking News: Technology Innovation",
     *       "slug": "breaking-news-technology-innovation",
     *       "excerpt": "A brief summary of the article...",
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
     *     "first": "http://localhost/api/v1/articles?page=1",
     *     "last": "http://localhost/api/v1/articles?page=10",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/articles?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 10,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 150
     *   }
     * }
     */
    public function index(ArticleIndexRequest $request): ArticleCollection
    {
        $filters = ArticleFilterDTO::fromRequest($request->validated());
        $articles = $this->articleService->getPaginated($filters);

        return new ArticleCollection($articles);
    }

    /**
     * Get article details
     *
     * Retrieve a single article by its ID.
     *
     * @unauthenticated
     *
     * @urlParam id integer required The ID of the article. Example: 1
     *
     * @response 200 scenario="Article found" {
     *   "data": {
     *     "id": 1,
     *     "title": "Breaking News: Technology Innovation",
     *     "slug": "breaking-news-technology-innovation",
     *     "excerpt": "A brief summary of the article...",
     *     "content": "Full article content here...",
     *     "url": "https://example.com/article",
     *     "image_url": "https://example.com/image.jpg",
     *     "published_at": "2024-01-15T10:30:00Z",
     *     "source": {
     *       "id": 1,
     *       "name": "NewsAPI",
     *       "slug": "newsapi"
     *     },
     *     "category": {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology"
     *     },
     *     "author": {
     *       "id": 1,
     *       "name": "Jane Smith"
     *     }
     *   }
     * }
     * @response 404 scenario="Article not found" {
     *   "message": "Article not found"
     * }
     */
    public function show(int $id): ArticleResource|JsonResponse
    {
        $article = $this->articleService->findById($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return new ArticleResource($article);
    }
}
