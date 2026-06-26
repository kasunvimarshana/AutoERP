<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\AuthorizeClientRequest;
use Modules\Auth\Http\Requests\ExchangeAuthorizationCodeRequest;
use Modules\Auth\Http\Requests\ListSessionsRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RevokeSessionRequest;
use Modules\Auth\Http\Resources\AuthPayloadResource;
use Modules\Auth\Services\AuthProfileService;
use Modules\Auth\Services\OAuthAuthorizationService;
use Modules\Auth\Services\TenantAuthenticationService;
use Modules\Auth\Services\TenantRefreshTokenCookie;
use Modules\Auth\Services\TenantSessionService;
use Modules\Auth\Services\TokenService;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class AuthController extends Controller
{
    public function __construct(
        private readonly TenantAuthenticationService $authentication,
        private readonly TokenService $tokens,
        private readonly TenantRefreshTokenCookie $cookie,
        private readonly TenantSessionService $sessions,
        private readonly OAuthAuthorizationService $oauth,
        private readonly AuthProfileService $profiles,
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $currentUser,
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

            return $this->respondWithRefreshCookie($this->completeTenantPayload($payload));
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $this->cookie->read($request);
        if ($refreshToken === null) {
            return $this->failure(new AuthFailure(
                AuthErrorCode::TOKEN_INVALID,
                'Refresh session is not available.',
                401,
            ));
        }

        try {
            return $this->respondWithRefreshCookie($this->completeTenantPayload([
                'tokens' => $this->tokens->refreshTenant($refreshToken),
            ]));
        } catch (AuthFailure $failure) {
            return $this->cookie->forget($this->failure($failure));
        }
    }

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

            return $this->noStore(response()->json(['data' => $data]));
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function exchange(ExchangeAuthorizationCodeRequest $request): JsonResponse
    {
        try {
            return $this->respondWithRefreshCookie($this->completeTenantPayload([
                'tokens' => $this->oauth->exchange(
                    (string) $request->validated('authorization_code'),
                    (string) $request->validated('client_key'),
                    is_string($request->validated('client_secret'))
                        ? $request->validated('client_secret')
                        : null,
                    (string) $request->validated('redirect_uri'),
                    (string) $request->validated('code_verifier'),
                ),
            ]));
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function me(): JsonResponse
    {
        try {
            return $this->payloadResponse(
                $this->profiles->tenant($this->currentUser->requireCurrent()->tokenPayload()),
            );
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function logout(): JsonResponse
    {
        $token = $this->currentUser->requireCurrent()->tokenPayload();
        $this->tokens->revokeTenantSession(
            (int) $token['tenant_id'],
            (int) $token['session_id'],
            (int) $token['tenant_user_id'],
            'User signed out.',
        );

        return $this->cookie->forget($this->noStore(response()->json(['success' => true])));
    }

    public function sessions(ListSessionsRequest $request): JsonResponse
    {
        $token = $this->tokenPayload($request);

        return $this->noStore(response()->json([
            'data' => $this->sessions->listForUser(
                (int) $token['tenant_id'],
                (int) $token['tenant_user_id'],
            ),
        ]));
    }

    public function revokeSession(RevokeSessionRequest $request, string $session): JsonResponse
    {
        $token = $this->tokenPayload($request);
        $this->sessions->revokeByPublicId(
            (int) $token['tenant_id'],
            (int) $token['tenant_user_id'],
            $session,
            (string) ($request->validated('reason') ?? 'User revoked session.'),
        );

        return $this->noStore(response()->json(['success' => true]));
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function completeTenantPayload(array $payload): array
    {
        $accessToken = (string) ($payload['tokens']['access_token'] ?? '');
        if ($accessToken === '') {
            throw new AuthFailure(
                AuthErrorCode::TOKEN_INVALID,
                'Authentication token issuance failed.',
                500,
            );
        }

        return array_merge($payload, $this->profiles->tenant(
            $this->tokens->validateAccessToken($accessToken),
        ));
    }

    /** @param array<string,mixed> $payload */
    private function respondWithRefreshCookie(array $payload): JsonResponse
    {
        $response = $this->payloadResponse($payload);
        $refreshToken = $this->cookie->extract($payload);

        return $refreshToken === null
            ? $response
            : $this->cookie->attach($response, $refreshToken, $this->cookie->extractExpiry($payload));
    }

    /** @param array<string,mixed> $payload */
    private function payloadResponse(array $payload): JsonResponse
    {
        return $this->noStore(response()->json(
            (new AuthPayloadResource($payload))->resolve(request()),
        ));
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

    private function failure(AuthFailure $failure): JsonResponse
    {
        return $this->noStore(response()->json([
            'message' => $failure->getMessage(),
            'code' => $failure->errorCode,
            'details' => $failure->details,
        ], $failure->httpStatus));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
