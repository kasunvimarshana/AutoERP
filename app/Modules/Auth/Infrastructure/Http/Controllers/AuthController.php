<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Auth\Application\Contracts\SsoServiceInterface;
use Modules\Auth\Application\UseCases\ForgotPassword;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Auth\Application\UseCases\LoginUser;
use Modules\Auth\Application\UseCases\LogoutUser;
use Modules\Auth\Application\UseCases\RefreshToken;
use Modules\Auth\Application\UseCases\RegisterUser;
use Modules\Auth\Application\UseCases\ResetPassword;
use Modules\Auth\Domain\Exceptions\AuthenticationException;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Modules\Auth\Infrastructure\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use Modules\Auth\Infrastructure\Http\Requests\RegisterRequest;
use Modules\Auth\Infrastructure\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Infrastructure\Http\Requests\SsoRequest;
use Modules\Auth\Infrastructure\Http\Resources\AuthTokenResource;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class AuthController extends AuthorizedController
{
    public function __construct(
        private readonly LoginUser $loginUser,
        private readonly LogoutUser $logoutUser,
        private readonly RegisterUser $registerUser,
        private readonly SsoServiceInterface $ssoService,
        private readonly GetAuthenticatedUser $getAuthenticatedUser,
        private readonly RefreshToken $refreshToken,
        private readonly ForgotPassword $forgotPassword,
        private readonly ResetPassword $resetPassword,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        description: 'Creates a new user account and returns a Passport access token.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_id', 'email', 'first_name', 'last_name', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'tenant_id',             type: 'integer', example: 1),
                    new OA\Property(property: 'email',                 type: 'string',  format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'first_name',            type: 'string',  example: 'John'),
                    new OA\Property(property: 'last_name',             type: 'string',  example: 'Doe'),
                    new OA\Property(property: 'password',              type: 'string',  format: 'password', minLength: 8, example: 'secret12'),
                    new OA\Property(property: 'password_confirmation', type: 'string',  format: 'password', example: 'secret12'),
                    new OA\Property(property: 'phone',                 type: 'string',  nullable: true, example: '+1-555-0100'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Registered successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->registerUser->execute($request->validated());

        return response()->json(
            (new AuthTokenResource($token))->toArray($request),
            201,
        );
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login',
        description: 'Authenticates a user with email and password and returns a Passport access token.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email',    type: 'string', format: 'email',    example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret12'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $token = $this->loginUser->execute(
                $validated['email'],
                $validated['password'],
            );
        } catch (InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json((new AuthTokenResource($token))->toArray($request));
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout',
        description: "Revokes the authenticated user's current access token.",
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function logout(): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->logoutUser->execute((int) $user->getAuthIdentifier());

        return response()->json(['message' => 'Logged out successfully']);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Get authenticated user',
        description: 'Returns the full profile of the currently authenticated user.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Profile unavailable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function me(): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $this->userRepository->find($authenticatable->getAuthIdentifier());

        if (! $user) {
            return response()->json(['message' => 'User profile unavailable'], 404);
        }

        return response()->json(new UserResource($user));
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh access token',
        description: 'Revokes the current token and issues a new access token (token rotation).',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token refreshed',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function refresh(): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = $this->refreshToken->execute((int) $user->getAuthIdentifier());

        return response()->json((new AuthTokenResource($token))->toArray(request()));
    }

    #[OA\Post(
        path: '/api/auth/forgot-password',
        summary: 'Request password reset link',
        description: 'Sends a password-reset link to the provided email address if it exists.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Reset link response',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $sent = $this->forgotPassword->execute($request->validated()['email']);

        return response()->json([
            'message' => $sent
                ? 'Password reset link sent to your email address.'
                : 'If that email exists, a reset link has been sent.',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/reset-password',
        summary: 'Reset password',
        description: "Resets the user's password using the signed token received via email.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email',                 type: 'string', format: 'email',    example: 'user@example.com'),
                    new OA\Property(property: 'token',                 type: 'string',                     example: 'abc123token'),
                    new OA\Property(property: 'password',              type: 'string', format: 'password', minLength: 8, example: 'newSecret1'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newSecret1'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Password reset',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Invalid or expired token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->resetPassword->execute($request->validated());
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    #[OA\Post(
        path: '/api/auth/sso/{provider}',
        summary: 'SSO token exchange',
        description: 'Exchanges a third-party SSO token for a local Passport access token.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'ya29.A0ARrdaM…'),
                ],
            ),
        ),
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                description: 'SSO provider identifier (e.g. google, microsoft)',
                schema: new OA\Schema(type: 'string', example: 'google'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'SSO exchange successful',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid SSO token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function ssoExchange(string $provider, SsoRequest $request): JsonResponse
    {
        try {
            $accessToken = $this->ssoService->exchangeToken(
                $request->validated()['token'],
                $provider,
            );
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json((new AuthTokenResource($accessToken))->toArray($request));
    }
}
