<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Services\UserPreferenceService;
use Illuminate\Http\Request;

/**
 * @group User Preferences
 *
 * APIs for managing user preferences.
 * Preferences control the personalized feed by specifying preferred sources, categories, and authors.
 */
class UserPreferenceController extends Controller
{
    public function __construct(
        private UserPreferenceService $preferenceService
    ) {}

    /**
     * Get user preferences
     *
     * Retrieve the current user's news preferences.
     * Returns an empty preferences object if none exist.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "preferred_sources": [1, 2],
     *     "preferred_categories": [1, 3, 5],
     *     "preferred_authors": [10, 15]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function show(Request $request): UserPreferenceResource
    {
        $preference = $this->preferenceService->getOrCreate($request->user());

        return new UserPreferenceResource($preference);
    }

    /**
     * Update user preferences
     *
     * Update the current user's news preferences.
     * Any field not provided will remain unchanged.
     *
     * @authenticated
     *
     * @bodyParam preferred_sources integer[] An array of source IDs the user prefers. Example: [1, 2]
     * @bodyParam preferred_categories integer[] An array of category IDs the user prefers. Example: [1, 3, 5]
     * @bodyParam preferred_authors integer[] An array of author IDs the user prefers. Example: [10, 15]
     *
     * @response 200 scenario="Preferences updated" {
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "preferred_sources": [1, 2],
     *     "preferred_categories": [1, 3, 5],
     *     "preferred_authors": [10, 15]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "One or more selected sources do not exist.",
     *   "errors": {
     *     "preferred_sources.0": ["One or more selected sources do not exist."]
     *   }
     * }
     */
    public function update(UserPreferenceRequest $request): UserPreferenceResource
    {
        $preference = $this->preferenceService->update(
            $request->user(),
            $request->validated()
        );

        return new UserPreferenceResource($preference);
    }
}
