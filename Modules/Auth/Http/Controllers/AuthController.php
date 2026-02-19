<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthenticationService;
use Modules\Auth\Services\UserService;
use Modules\Core\Exceptions\AuthorizationException;
use Modules\Tenant\Exceptions\OrganizationNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthController
 *
 * Handles authentication operations: login, register, refresh, logout, me
 */
class AuthController extends ApiController
{
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected UserService $userService
    ) {}

    /**
     * User login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only(['email', 'password', 'organization_id']);

            $result = $this->authenticationService->login(
                $credentials,
                $request->input('device_name', 'Unknown Device'),
                $request->userAgent() ?? 'Unknown',
                $request->ip() ?? '0.0.0.0'
            );

            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (InvalidCredentialsException $e) {
            return $this->error($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return $this->error('Login failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * User registration
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userData = $request->only(['name', 'email', 'password', 'organization_id']);
            $user = $this->userService->createUser($userData, $userData['organization_id']);

            $result = $this->authenticationService->login(
                [
                    'email' => $user->email,
                    'password' => $request->input('password'),
                    'organization_id' => $user->organization_id,
                ],
                $request->input('device_name', 'Unknown Device'),
                $request->userAgent() ?? 'Unknown',
                $request->ip() ?? '0.0.0.0'
            );

            return $this->created([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Registration successful');
        } catch (OrganizationNotFoundException $e) {
            return $this->error('Organization not found', Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->error('Registration failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh authentication token
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $oldToken = $request->input('token');
            $result = $this->authenticationService->refreshToken($oldToken);

            return $this->success([
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
        } catch (AuthorizationException $e) {
            return $this->error($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return $this->error('Token refresh failed', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Logout (revoke token)
     */
    public function logout(): JsonResponse
    {
        try {
            $token = request()->bearerToken();

            if (! $token) {
                return $this->error('No token provided', Response::HTTP_BAD_REQUEST);
            }

            $this->authenticationService->logout($token);

            return $this->success(null, 'Logout successful');
        } catch (AuthorizationException $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            return $this->error('Logout failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get authenticated user details
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Not authenticated');
        }

        return $this->success(
            new UserResource($user->load(['roles', 'permissions', 'organization', 'devices']))
        );
    }
}
