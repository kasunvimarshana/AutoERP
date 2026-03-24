<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
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

class AuthController extends Controller
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

    /**
     * Register a new user and return an access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->registerUser->execute($request->validated());

        return response()->json(
            (new AuthTokenResource($token))->toArray($request),
            201,
        );
    }

    /**
     * Authenticate and return an access token.
     */
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

    /**
     * Revoke the current token (logout).
     */
    public function logout(): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->logoutUser->execute((int) $user->getAuthIdentifier());

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Return the currently authenticated user.
     */
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

    /**
     * Revoke the current token and issue a fresh replacement (token rotation).
     */
    public function refresh(): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = $this->refreshToken->execute((int) $user->getAuthIdentifier());

        return response()->json((new AuthTokenResource($token))->toArray(request()));
    }

    /**
     * Send a password-reset link to the given email address.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $sent = $this->forgotPassword->execute($request->validated()['email']);

        return response()->json([
            'message' => $sent
                ? 'Password reset link sent to your email address.'
                : 'If that email exists, a reset link has been sent.',
        ]);
    }

    /**
     * Reset the user's password using the signed token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->resetPassword->execute($request->validated());
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    /**
     * Exchange an SSO token for a local access token.
     */
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
