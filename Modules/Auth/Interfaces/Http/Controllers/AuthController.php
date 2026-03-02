<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Commands\LoginCommand;
use Modules\Auth\Application\Commands\RegisterCommand;
use Modules\Auth\Application\Handlers\LoginHandler;
use Modules\Auth\Application\Handlers\RegisterHandler;
use Modules\Auth\Interfaces\Http\Requests\LoginRequest;
use Modules\Auth\Interfaces\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\AuthenticationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginHandler    $loginHandler,
        private readonly RegisterHandler $registerHandler,
    ) {}

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginHandler->handle(new LoginCommand(
                email: $request->input('email'),
                password: $request->input('password'),
                deviceName: $request->input('device_name', 'default'),
            ));

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => $result,
                'errors'  => null,
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['credentials' => [$e->getMessage()]],
            ], 401);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['email' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'       => 'required|integer|exists:tenants,id',
            'organisation_id' => 'required|integer|exists:organisations,id',
            'name'            => 'required|string|max:255',
            'email'           => 'required|email',
            'password'        => 'required|string|min:8|confirmed',
            'role'            => 'nullable|string',
        ]);

        try {
            $user = $this->registerHandler->handle(new RegisterCommand(
                tenantId: (int) $validated['tenant_id'],
                organisationId: (int) $validated['organisation_id'],
                name: $validated['name'],
                email: $validated['email'],
                password: $validated['password'],
                role: $validated['role'] ?? 'user',
            ));

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'data'    => [
                    'id'              => $user->getId(),
                    'name'            => $user->getName(),
                    'email'           => $user->getEmail()->getValue(),
                    'tenant_id'       => $user->getTenantId(),
                    'organisation_id' => $user->getOrganisationId(),
                ],
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['email' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully.',
                'data'    => [
                    'token'      => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl', 60) * 60,
                ],
                'errors'  => null,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired and can no longer be refreshed.',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved.',
            'data'    => new UserResource($user),
            'errors'  => null,
        ]);
    }
}
