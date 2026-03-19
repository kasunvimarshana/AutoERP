<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class TokenService
{
    private const ACCESS_TOKEN_EXPIRY_MINUTES = 15;

    public function __construct(
        private readonly RevocationService $revocationService,
    ) {}

    public function issueTokenPair(User $user, string $deviceId = ''): array
    {
        $scopes = $this->getUserScopes($user);

        // Passport v12 createToken($name, $scopes)
        $tokenResult = $user->createToken('access_token', $scopes);

        // In Passport v12: ->accessToken = plain text token, ->token = Token model
        $jti = $tokenResult->token->id;

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_in'   => self::ACCESS_TOKEN_EXPIRY_MINUTES * 60,
            'jti'          => $jti,
            'claims'       => $this->buildClaims($user, $deviceId),
        ];
    }

    public function revokeUserToken(string $jti, int $userId): void
    {
        $this->revocationService->revokeToken($jti, self::ACCESS_TOKEN_EXPIRY_MINUTES * 60);
    }

    public function revokeAllUserTokens(User $user): void
    {
        $user->tokens()->update(['revoked' => true]);
        $this->revocationService->revokeAllUserTokens($user->id);
    }

    public function revokeDeviceTokens(User $user, string $deviceId): void
    {
        $this->revocationService->revokeUserDeviceTokens($user->id, $deviceId);
    }

    public function validateTokenVersion(User $user, int $tokenVersion): bool
    {
        return $user->token_version === $tokenVersion;
    }

    private function getUserScopes(User $user): array
    {
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        return $permissions;
    }

    private function buildClaims(User $user, string $deviceId): array
    {
        return [
            'user_id'         => $user->id,
            'tenant_id'       => $user->tenant_id,
            'organization_id' => $user->organization_id,
            'branch_id'       => $user->branch_id,
            'device_id'       => $deviceId ?: null,
            'token_version'   => $user->token_version,
            'roles'           => $user->getRoleNames()->toArray(),
        ];
    }
}
