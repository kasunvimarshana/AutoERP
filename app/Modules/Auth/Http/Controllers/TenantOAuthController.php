<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\AuthorizeClientRequest;
use Modules\Auth\Http\Requests\ExchangeAuthorizationCodeRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\OAuthAuthorizationService;
use Modules\Auth\Services\TenantAuthProfileBuilder;
use Modules\Auth\Services\TenantRefreshTokenCookie;
use Modules\Auth\Services\TenantTokenService;

final class TenantOAuthController extends Controller
{
    public function __construct(
        private readonly OAuthAuthorizationService $oauth,
        private readonly TenantTokenService $tokens,
        private readonly TenantAuthProfileBuilder $profiles,
        private readonly TenantRefreshTokenCookie $cookie,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function authorize(AuthorizeClientRequest $request): JsonResponse
    {
        try {
            $token = $this->tokenPayload($request);
            $data = $this->oauth->authorize(
                (int) $token['tenant_id'],
                (int) $token['tenant_user_id'],
                (int) $token['session_id'],
                (string) $request->validated('client_key'),
                (string) $request->validated('redirect_uri'),
                $request->validated('scopes'),
                (string) $request->validated('code_challenge'),
                is_string($request->validated('state')) ? $request->validated('state') : null,
            );

            return $this->responses->success(['data' => $data]);
        } catch (AuthFailure $failure) {
            return $this->responses->failure($failure);
        }
    }

    public function exchange(ExchangeAuthorizationCodeRequest $request): JsonResponse
    {
        try {
            $tokens = $this->oauth->exchange(
                (string) $request->validated('authorization_code'),
                (string) $request->validated('client_key'),
                is_string($request->validated('client_secret'))
                    ? $request->validated('client_secret')
                    : null,
                (string) $request->validated('redirect_uri'),
                (string) $request->validated('code_verifier'),
            );
            $accessToken = $this->accessToken($tokens);
            $payload = array_merge(
                ['tokens' => $tokens],
                $this->profiles->build($this->tokens->validateAccessToken($accessToken)),
            );
            $response = $this->responses->payload($payload);
            $refreshToken = $this->cookie->extract($payload);

            return $refreshToken === null
                ? $response
                : $this->cookie->attach($response, $refreshToken, $this->cookie->extractExpiry($payload));
        } catch (AuthFailure $failure) {
            return $this->responses->failure($failure);
        }
    }

    /** @return array<string,mixed> */
    private function tokenPayload(Request $request): array
    {
        $payload = $request->attributes->get('auth_access_token');
        if (! is_array($payload)) {
            throw new AuthFailure(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Authentication is required.',
                401,
            );
        }

        return $payload;
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
