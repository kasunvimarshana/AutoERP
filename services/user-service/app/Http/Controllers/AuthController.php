<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(private readonly AuthService $authService) {}

    // -------------------------------------------------------------------------
    // POST /api/auth/login
    // -------------------------------------------------------------------------

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                email:    $request->validated('email'),
                password: $request->validated('password'),
                tenantId: $request->header('X-Tenant-ID'),
            );

            return $this->successResponse($result, 'Login successful');
        } catch (AuthenticationException $e) {
            return $this->errorResponse($e->getMessage(), null, 401);
        } catch (\Throwable $e) {
            return $this->errorResponse('Login failed', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/logout
    // -------------------------------------------------------------------------

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $this->authService->logout($user);

        return $this->successResponse(null, 'Logged out successfully');
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/refresh
    // -------------------------------------------------------------------------

    public function refresh(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $result = $this->authService->refresh($user);

        return $this->successResponse($result, 'Token refreshed');
    }

    // -------------------------------------------------------------------------
    // GET /api/auth/me
    // -------------------------------------------------------------------------

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        return $this->successResponse($user, 'Authenticated user');
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/sso
    // -------------------------------------------------------------------------

    /**
     * Exchange a validated SSO assertion for a local Passport access token.
     * In production this endpoint would verify the incoming SSO token via
     * the provider SDK before calling ssoLogin.
     */
    public function ssoLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'email'],
            'name'        => ['required', 'string', 'max:255'],
            'provider'    => ['required', 'string', 'in:google,microsoft,github,okta,saml'],
            'provider_id' => ['required', 'string'],
            'tenant_id'   => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->authService->ssoLogin([
                'email'       => $request->input('email'),
                'name'        => $request->input('name'),
                'provider'    => $request->input('provider'),
                'provider_id' => $request->input('provider_id'),
                'tenant_id'   => $request->input('tenant_id') ?? $request->header('X-Tenant-ID'),
            ]);

            return $this->successResponse($result, 'SSO login successful');
        } catch (\Throwable $e) {
            return $this->errorResponse('SSO login failed', $e->getMessage(), 500);
        }
    }
}
