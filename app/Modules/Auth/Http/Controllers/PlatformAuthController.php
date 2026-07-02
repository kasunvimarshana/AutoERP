<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\PlatformLoginRequest;
use Modules\Auth\Http\Requests\PlatformRefreshTokenRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\PlatformAuthenticationService;
use Modules\Auth\Services\PlatformAuthProfileBuilder;
use Modules\Auth\Services\PlatformRefreshTokenCookie;
use Modules\Auth\Services\PlatformTokenService;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class PlatformAuthController extends Controller
{
    public function __construct(
        private readonly PlatformAuthenticationService $authentication,
        private readonly PlatformTokenService $tokens,
        private readonly PlatformRefreshTokenCookie $cookie,
        private readonly PlatformAuthProfileBuilder $profiles,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->authentication->login(
                (string) $request->validated('email'),
                (string) $request->validated('password'),
                ClientContext::fromRequest(
                    $request,
                    is_string($request->validated('device_name'))
                        ? $request->validated('device_name')
                        : null,
                ),
            );

            return $this->withRefreshCookie($payload);
        } catch (AuthFailure $failure) {
            return $this->responses->failure($failure);
        }
    }

    public function refresh(PlatformRefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $this->cookie->read($request);
        if ($refreshToken === null) {
            return $this->responses->failure(new AuthFailure(
                AuthErrorCode::TOKEN_INVALID,
                'Refresh session is not available.',
                401,
            ));
        }

        try {
            $tokens = $this->tokens->refresh($refreshToken);
            $accessToken = $this->accessToken($tokens);
            $payload = array_merge(
                ['tokens' => $tokens],
                $this->profiles->build($this->tokens->validateAccessToken($accessToken)),
            );

            return $this->withRefreshCookie($payload);
        } catch (AuthFailure $failure) {
            return $this->cookie->forget($this->responses->failure($failure));
        }
    }

    public function me(): JsonResponse
    {
        try {
            return $this->responses->payload(
                $this->profiles->build($this->currentUser->requireCurrent()->tokenPayload()),
            );
        } catch (AuthFailure $failure) {
            return $this->responses->failure($failure);
        }
    }

    public function logout(): JsonResponse
    {
        $payload = $this->currentUser->requireCurrent()->tokenPayload();
        $this->tokens->revokeSession(
            (int) $payload['session_id'],
            (int) $payload['platform_operator_id'],
            'Platform operator signed out.',
        );

        return $this->cookie->forget($this->responses->success());
    }

    /** @param array<string,mixed> $payload */
    private function withRefreshCookie(array $payload): JsonResponse
    {
        $response = $this->responses->payload($payload);
        $refreshToken = $this->cookie->extract($payload);

        return $refreshToken === null
            ? $response
            : $this->cookie->attach($response, $refreshToken, $this->cookie->extractExpiry($payload));
    }

    /** @param array<string,mixed> $tokens */
    private function accessToken(array $tokens): string
    {
        $accessToken = trim((string) ($tokens['access_token'] ?? ''));
        if ($accessToken === '') {
            throw new AuthFailure(
                AuthErrorCode::TOKEN_INVALID,
                'Authentication token issuance failed.',
                500,
            );
        }

        return $accessToken;
    }
}
