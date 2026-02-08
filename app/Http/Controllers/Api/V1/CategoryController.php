<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Categories
 *
 * APIs for retrieving article categories.
 * Categories help organize articles by topic (e.g., Technology, Sports, Business).
 */
class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * List all categories
     *
     * Retrieve a list of all article categories.
     *
     * @unauthenticated
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology",
     *       "description": "Technology and innovation news"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Business",
     *       "slug": "business",
     *       "description": "Business and finance news"
     *     },
     *     {
     *       "id": 3,
     *       "name": "Sports",
     *       "slug": "sports",
     *       "description": "Sports news and updates"
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = $this->categoryService->getAll();

        return CategoryResource::collection($categories);
    }

    /**
     * Get category details
     *
     * Retrieve details of a specific category by its ID.
     *
     * @unauthenticated
     *
     * @urlParam id integer required The ID of the category. Example: 1
     *
     * @response 200 scenario="Category found" {
     *   "data": {
     *     "id": 1,
     *     "name": "Technology",
     *     "slug": "technology",
     *     "description": "Technology and innovation news"
     *   }
     * }
     * @response 404 scenario="Category not found" {
     *   "message": "Category not found"
     * }
     */
    public function show(int $id): CategoryResource|JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return new CategoryResource($category);
    }
}
