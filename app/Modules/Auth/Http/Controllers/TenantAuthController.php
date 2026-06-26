<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\TenantAuthenticationService;
use Modules\Auth\Services\TenantAuthProfileBuilder;
use Modules\Auth\Services\TenantRefreshTokenCookie;
use Modules\Auth\Services\TenantTokenService;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class TenantAuthController extends Controller
{
    public function __construct(
        private readonly TenantAuthenticationService $authentication,
        private readonly TenantTokenService $tokens,
        private readonly TenantRefreshTokenCookie $cookie,
        private readonly TenantAuthProfileBuilder $profiles,
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->authentication->login(
                $this->tenant->requireCurrent()->tenantId(),
                (string) $request->validated('identifier'),
                (string) $request->validated('password'),
                is_numeric($request->validated('organization_unit_id'))
                    ? (int) $request->validated('organization_unit_id')
                    : null,
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

    public function refresh(RefreshTokenRequest $request): JsonResponse
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
        $token = $this->currentUser->requireCurrent()->tokenPayload();
        $this->tokens->revokeSession(
            (int) $token['tenant_id'],
            (int) $token['session_id'],
            (int) $token['tenant_user_id'],
            'User signed out.',
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
