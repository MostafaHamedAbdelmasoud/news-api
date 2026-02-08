<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\PersonalizedFeedController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SourceController;
use App\Http\Controllers\Api\V1\UserPreferenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public routes
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/search', [SearchController::class, 'search']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);

    Route::get('/sources', [SourceController::class, 'index']);
    Route::get('/sources/{id}', [SourceController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{id}', [AuthorController::class, 'show']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/user/preferences', [UserPreferenceController::class, 'show']);
        Route::put('/user/preferences', [UserPreferenceController::class, 'update']);

        Route::get('/user/feed', [PersonalizedFeedController::class, 'index']);
    });
});
