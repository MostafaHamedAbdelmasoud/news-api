<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCollection;
use App\Services\ArticleService;
use Illuminate\Http\Request;

/**
 * @group User Feed
 *
 * APIs for personalized news feed.
 * The feed is customized based on user preferences (preferred sources, categories, and authors).
 */
class PersonalizedFeedController extends Controller
{
    public function __construct(
        private ArticleService $articleService
    ) {}

    /**
     * Get personalized feed
     *
     * Retrieve a personalized news feed based on user preferences.
     * Articles are filtered by the user's preferred sources, categories, and authors.
     *
     * @authenticated
     *
     * @queryParam per_page integer Number of articles per page (max 100, default: 15). Example: 20
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Breaking News in Your Interests",
     *       "slug": "breaking-news-in-your-interests",
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
     *     "first": "http://localhost/api/v1/user/feed?page=1",
     *     "last": "http://localhost/api/v1/user/feed?page=10",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/user/feed?page=2"
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
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function index(Request $request): ArticleCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $articles = $this->articleService->getPersonalizedFeed($request->user()->id, $perPage);

        return new ArticleCollection($articles);
    }
}
