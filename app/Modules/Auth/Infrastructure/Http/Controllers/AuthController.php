<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
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
use Modules\Auth\Infrastructure\Http\Resources\AuthenticatedUserResource;
use Modules\Auth\Infrastructure\Http\Resources\AuthTokenResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->registerUser->execute($request->validated());

        return response()->json(
            (new AuthTokenResource($token))->toArray($request),
            HttpResponse::HTTP_CREATED,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $token = $this->loginUser->execute(
                $validated['email'],
                $validated['password'],
            );
        } catch (InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], HttpResponse::HTTP_UNAUTHORIZED);
        }

        return response()->json((new AuthTokenResource($token))->toArray($request));
    }

    public function logout(): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $this->logoutUser->execute((int) $user->getAuthIdentifier());

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        return response()->json(new AuthenticatedUserResource($authenticatable));
    }

    public function refresh(HttpRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser->execute();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $token = $this->refreshToken->execute((int) $user->getAuthIdentifier());

        return response()->json((new AuthTokenResource($token))->toArray($request));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $sent = $this->forgotPassword->execute($request->validated()['email']);

        return response()->json([
            'message' => $sent
                ? 'Password reset link sent to your email address.'
                : 'If that email exists, a reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->resetPassword->execute($request->validated());
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    public function ssoExchange(string $provider, SsoRequest $request): JsonResponse
    {
        try {
            $accessToken = $this->ssoService->exchangeToken(
                $request->validated()['token'],
                $provider,
            );
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], HttpResponse::HTTP_UNAUTHORIZED);
        }

        return response()->json((new AuthTokenResource($accessToken))->toArray($request));
    }
}
