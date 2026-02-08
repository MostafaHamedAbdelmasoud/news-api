<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * APIs for user authentication and registration.
 * These endpoints allow users to register, login, and logout.
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register a new user
     *
     * Create a new user account and receive an authentication token.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Must be unique. Example: john@example.com
     * @bodyParam password string required The password. Must be at least 8 characters. Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Must match password. Example: password123
     *
     * @response 201 scenario="Registration successful" {
     *   "message": "User registered successfully",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "token": "1|abcdef123456..."
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password')
        );

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Login
     *
     * Authenticate a user and receive an access token.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 scenario="Login successful" {
     *   "message": "Login successful",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "token": "2|xyz789..."
     * }
     * @response 401 scenario="Invalid credentials" {
     *   "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        if (! $result) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current user's access token.
     *
     * @authenticated
     *
     * @response 200 scenario="Logout successful" {
     *   "message": "Logged out successfully"
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
