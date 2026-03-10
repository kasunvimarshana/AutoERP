<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auth\DTOs\LoginDTO;
use App\Application\Auth\DTOs\RegisterDTO;
use App\Application\Auth\Services\AuthService;
use App\Domain\User\Exceptions\InvalidCredentialsException;
use App\Domain\User\Exceptions\UserAlreadyExistsException;
use App\Domain\User\Exceptions\UserInactiveException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * AuthController
 *
 * Thin controller — delegates all logic to AuthService.
 * Handles only: request parsing, response formatting, and exception mapping.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register(
                RegisterDTO::fromArray($request->validated())
            );

            return (new AuthResource($result))
                ->response()
                ->setStatusCode(201);

        } catch (UserAlreadyExistsException $e) {
            return $this->errorResponse($e->getMessage(), 409);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                LoginDTO::fromArray($request->validated())
            );

            return (new AuthResource($result))->response();

        } catch (InvalidCredentialsException $e) {
            return $this->errorResponse($e->getMessage(), 401);
        } catch (UserInactiveException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/logout
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/refresh
    // ─────────────────────────────────────────────────────────────────────────

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        return response()->json([
            'data' => $result,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/auth/me
    // ─────────────────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error'   => true,
        ], $status);
    }
}
