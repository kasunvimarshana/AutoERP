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
use Modules\Auth\Services\RefreshTokenService;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Result;

final class PlatformAuthController extends Controller
{
    public function __construct(
        private readonly PlatformLoginService $login,
        private readonly RefreshTokenService $refreshTokens,
        private readonly TokenProviderInterface $tokens,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly ApiErrorResponseFactory $errors,
    ) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        $result = $this->login->login(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            (string) $request->ip(),
        );

        return $this->respond($result);
    }

    public function refresh(PlatformRefreshTokenRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tenant_id'] = null;
        $payload['token_scope'] = AuthTokenScope::PLATFORM;
        $payload['scopes'] = ['platform'];

        return $this->respond(
            $this->refreshTokens->refreshToken(TokenRefreshData::fromArray($payload)),
        );
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
        $token = request()->bearerToken();
        if (is_string($token) && $token !== '') {
            $this->tokens->revokeAccessToken($token);
        }

        return response()->json(['success' => true]);
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
