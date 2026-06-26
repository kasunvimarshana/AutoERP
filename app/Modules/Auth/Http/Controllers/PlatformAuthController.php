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
use Modules\Auth\Http\Resources\AuthPayloadResource;
use Modules\Auth\Services\AuthProfileService;
use Modules\Auth\Services\PlatformAuthenticationService;
use Modules\Auth\Services\PlatformRefreshTokenCookie;
use Modules\Auth\Services\TokenService;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class PlatformAuthController extends Controller
{
    public function __construct(
        private readonly PlatformAuthenticationService $authentication,
        private readonly TokenService $tokens,
        private readonly PlatformRefreshTokenCookie $cookie,
        private readonly AuthProfileService $profiles,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->authentication->login(
                (string) $request->validated('email'),
                (string) $request->validated('password'),
                is_string($request->validated('totp_code')) ? $request->validated('totp_code') : null,
                is_string($request->validated('backup_code')) ? $request->validated('backup_code') : null,
                ClientContext::fromRequest(
                    $request,
                    is_string($request->validated('device_name'))
                        ? $request->validated('device_name')
                        : null,
                ),
            );

            return $this->respondWithRefreshCookie($this->completePlatformPayload($payload));
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function refresh(PlatformRefreshTokenRequest $request): JsonResponse
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
            return $this->respondWithRefreshCookie($this->completePlatformPayload([
                'tokens' => $this->tokens->refreshPlatform($refreshToken),
            ]));
        } catch (AuthFailure $failure) {
            return $this->cookie->forget($this->failure($failure));
        }
    }

    public function me(): JsonResponse
    {
        try {
            return $this->payloadResponse(
                $this->profiles->platform($this->currentUser->requireCurrent()->tokenPayload()),
            );
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function logout(): JsonResponse
    {
        $payload = $this->currentUser->requireCurrent()->tokenPayload();
        $this->tokens->revokePlatformSession(
            (int) $payload['session_id'],
            (int) $payload['platform_operator_id'],
            'Platform operator signed out.',
        );

        return $this->cookie->forget($this->noStore(response()->json(['success' => true])));
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function completePlatformPayload(array $payload): array
    {
        $accessToken = (string) ($payload['tokens']['access_token'] ?? '');
        if ($accessToken === '') {
            throw new AuthFailure(
                AuthErrorCode::TOKEN_INVALID,
                'Authentication token issuance failed.',
                500,
            );
        }

        return array_merge($payload, $this->profiles->platform(
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
