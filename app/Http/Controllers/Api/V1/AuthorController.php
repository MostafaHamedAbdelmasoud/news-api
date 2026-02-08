<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Authors
 *
 * APIs for retrieving article authors.
 * Authors are the writers or journalists who create the articles.
 */
class AuthorController extends Controller
{
    public function __construct(
        private AuthorService $authorService
    ) {}

    /**
     * List authors
     *
     * Retrieve a paginated list of authors. Optionally filter by source.
     *
     * @unauthenticated
     *
     * @queryParam source_id integer Filter authors by source ID. Example: 1
     * @queryParam per_page integer Number of authors per page (default: 15). Example: 20
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Jane Smith",
     *       "source_id": 1
     *     },
     *     {
     *       "id": 2,
     *       "name": "John Doe",
     *       "source_id": 2
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/authors?page=1",
     *     "last": "http://localhost/api/v1/authors?page=5",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/authors?page=2"
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
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $sourceId = $request->has('source_id') ? (int) $request->input('source_id') : null;
        $perPage = (int) $request->input('per_page', 15);

        $authors = $this->authorService->getPaginated($sourceId, $perPage);

        return AuthorResource::collection($authors);
    }

    /**
     * Get author details
     *
     * Retrieve details of a specific author by their ID.
     *
     * @unauthenticated
     *
     * @urlParam id integer required The ID of the author. Example: 1
     *
     * @response 200 scenario="Author found" {
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Smith",
     *     "source_id": 1
     *   }
     * }
     * @response 404 scenario="Author not found" {
     *   "message": "Author not found"
     * }
     */
    public function show(int $id): AuthorResource|JsonResponse
    {
        $author = $this->authorService->findById($id);

        if (! $author) {
            return response()->json(['message' => 'Author not found'], 404);
        }

        return new AuthorResource($author);
    }
}
