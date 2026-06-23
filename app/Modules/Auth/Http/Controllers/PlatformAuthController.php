<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenScope;
use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\Http\Requests\PlatformLoginRequest;
use Modules\Auth\Http\Requests\PlatformRefreshTokenRequest;
use Modules\Auth\Http\Resources\AuthPayloadResource;
use Modules\Auth\Services\PlatformLoginService;
use Modules\Auth\Services\RefreshTokenCookie;
use Modules\Auth\Services\RefreshTokenService;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Result;

final class PlatformAuthController extends Controller
{
    public function __construct(
        private readonly PlatformLoginService $login,
        private readonly RefreshTokenService $refreshTokens,
        private readonly RefreshTokenCookie $refreshTokenCookie,
        private readonly TokenProviderInterface $tokens,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly ApiErrorResponseFactory $errors,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        $result = $this->login->login(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            (string) $request->ip(),
        );

        return $this->respondWithRefreshCookie($result);
    }

    public function refresh(PlatformRefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenCookie->read($request);
        if ($refreshToken === null) {
            return $this->errors->make(
                AuthErrorCode::TOKEN_INVALID,
                'Refresh session is not available.',
                401,
                'authentication',
            );
        }

        $payload = $request->validated();
        $payload['refresh_token'] = $refreshToken;
        $payload['tenant_id'] = null;
        $payload['token_scope'] = AuthTokenScope::PLATFORM;
        $payload['scopes'] = ['platform'];
        $payload['access_token_ttl_seconds'] = (int) config('module-auth.access_token_ttl_seconds');
        $payload['refresh_token_ttl_seconds'] = (int) config('module-auth.refresh_token_ttl_seconds');

        return $this->executionContext->runAsControlPlane(function () use ($payload): JsonResponse {
            $result = $this->refreshTokens->refreshToken(TokenRefreshData::fromArray($payload));
            $response = $this->respondWithRefreshCookie($result);

            return $result->isFailure()
                ? $this->refreshTokenCookie->forget($response)
                : $response;
        });
    }

    public function me(): JsonResponse
    {
        $context = $this->currentUser->current();
        if ($context === null) {
            return $this->errors->make(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Platform authentication is required.',
                401,
                'authentication',
            );
        }

        $user = $context->user();

        return response()->json((new AuthPayloadResource([
            'user' => [
                'id' => $context->userId(),
                'first_name' => $user->getAttribute('first_name'),
                'last_name' => $user->getAttribute('last_name'),
                'email' => $user->getAttribute('platform_login_email') ?? $user->getAttribute('email'),
                'is_platform_operator' => true,
            ],
            'tenant' => null,
            'organization_unit' => null,
            'roles' => ['Platform Operator'],
            'permissions' => [],
            'enabled_modules' => null,
            'is_platform_operator' => true,
        ]))->resolve(request()));
    }

    public function logout(): JsonResponse
    {
        $request = request();
        $token = $request->bearerToken();
        if (is_string($token) && $token !== '') {
            $this->tokens->revokeAccessToken($token);
        }

        $refreshToken = $this->refreshTokenCookie->read($request);
        if ($refreshToken !== null) {
            $this->tokens->revokeRefreshToken($refreshToken);
        }

        return $this->refreshTokenCookie->forget(response()->json(['success' => true]));
    }

    private function respondWithRefreshCookie(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            return $this->respond($result);
        }

        $payload = $result->valueOrFail();
        $response = response()->json(
            (new AuthPayloadResource($payload))->resolve(request()),
        );
        $refreshToken = $this->refreshTokenCookie->extract($payload);

        return $refreshToken === null
            ? $response
            : $this->refreshTokenCookie->attach($response, $refreshToken);
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return $this->errors->make($error->code, $error->message, 401, 'authentication');
        }

        return response()->json(
            (new AuthPayloadResource($result->valueOrFail()))->resolve(request()),
        );
    }
}
