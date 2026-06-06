<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Auth\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthSessionRepositoryInterface;
use Modules\Core\Contracts\PasswordHasherInterface;

final class DatabaseTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly AuthAccessTokenRepositoryInterface $accessTokens,
        private readonly AuthRefreshTokenRepositoryInterface $refreshTokens,
        private readonly AuthSessionRepositoryInterface $sessions,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function issue(TokenIssueData $data): array
    {
        $accessKey = Str::random(48);
        $accessSecret = Str::random(72);
        $refreshKey = Str::random(48);
        $refreshSecret = Str::random(72);

        $issuedAt = now();
        $accessExpiresAt = now()->addSeconds($data->accessTokenTtlSeconds);
        $refreshExpiresAt = now()->addSeconds($data->refreshTokenTtlSeconds);

        $access = $this->accessTokens->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'provider_id' => $data->providerId,
            'client_id' => $data->clientId,
            'identity_id' => $data->identityId,
            'session_id' => $data->sessionId,
            'user_id' => $data->userId,
            'token_key' => $accessKey,
            'token_hash' => $this->passwordHasher->hash($accessSecret),
            'scopes' => $data->scopes,
            'grant_type' => $data->grantType,
            'status' => 'active',
            'issued_at' => $issuedAt,
            'expires_at' => $accessExpiresAt,
            'row_version' => 1,
            'metadata' => $data->metadata,
        ]);

        $refresh = $this->refreshTokens->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'access_token_id' => (int) $access->id(),
            'client_id' => $data->clientId,
            'identity_id' => $data->identityId,
            'session_id' => $data->sessionId,
            'user_id' => $data->userId,
            'refresh_key' => $refreshKey,
            'refresh_hash' => $this->passwordHasher->hash($refreshSecret),
            'status' => 'active',
            'issued_at' => $issuedAt,
            'expires_at' => $refreshExpiresAt,
            'row_version' => 1,
            'metadata' => $data->metadata,
        ]);

        return [
            'access_token' => $accessKey.'.'.$accessSecret,
            'refresh_token' => $refreshKey.'.'.$refreshSecret,
            'token_type' => 'Bearer',
            'access_token_expires_at' => $accessExpiresAt,
            'refresh_token_expires_at' => $refreshExpiresAt,
            'access_token_id' => $access->id(),
            'refresh_token_id' => $refresh->id(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function refresh(TokenRefreshData $data): ?array
    {
        [$refreshKey, $refreshSecret] = $this->splitToken($data->refreshToken);
        if ($refreshKey === null || $refreshSecret === null) {
            return null;
        }

        $existing = $this->refreshTokens->findActiveByRefreshKey($data->tenantId, $refreshKey);
        if ($existing === null) {
            return null;
        }

        if (! $this->passwordHasher->verify($refreshSecret, (string) $existing->get('refresh_hash', ''))) {
            return null;
        }

        $sessionId = $existing->get('session_id');
        if ($sessionId !== null) {
            $session = $this->sessions->findById((int) $sessionId);
            if ($session === null || (string) $session->get('status', '') !== 'active') {
                $this->refreshTokens->update($existing->id(), [
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return null;
            }

            $sessionTenantId = $session->get('tenant_id');
            if (
                $data->tenantId !== null
                && (int) ($sessionTenantId ?? 0) !== $data->tenantId
            ) {
                return null;
            }
        }

        $expiresAt = $existing->get('expires_at');
        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            $this->refreshTokens->update($existing->id(), [
                'status' => 'expired',
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return null;
        }

        $this->refreshTokens->update($existing->id(), [
            'status' => 'revoked',
            'rotated' => true,
            'rotated_at' => now(),
            'row_version' => ((int) $existing->get('row_version', 1)) + 1,
        ]);

        return $this->issue(TokenIssueData::fromArray([
            'tenant_id' => $existing->get('tenant_id'),
            'organization_unit_id' => $existing->get('organization_unit_id'),
            'client_id' => $existing->get('client_id'),
            'identity_id' => $existing->get('identity_id'),
            'session_id' => $existing->get('session_id'),
            'user_id' => $existing->get('user_id'),
            'grant_type' => 'refresh_token',
            'scopes' => $data->scopes,
            'access_token_ttl_seconds' => $data->accessTokenTtlSeconds,
            'refresh_token_ttl_seconds' => $data->refreshTokenTtlSeconds,
            'metadata' => $existing->get('metadata'),
        ]));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validate(string $plainAccessToken, ?int $tenantId = null): ?array
    {
        [$tokenKey, $tokenSecret] = $this->splitToken($plainAccessToken);
        if ($tokenKey === null || $tokenSecret === null) {
            return null;
        }

        $record = $this->accessTokens->findActiveByTokenKey($tenantId, $tokenKey);
        if ($record === null) {
            return null;
        }

        if (! $this->passwordHasher->verify($tokenSecret, (string) $record->get('token_hash', ''))) {
            return null;
        }

        $expiresAt = $record->get('expires_at');
        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            $this->accessTokens->update($record->id(), [
                'status' => 'expired',
                'row_version' => ((int) $record->get('row_version', 1)) + 1,
            ]);

            return null;
        }

        return $record->toArray();
    }

    public function revokeAccessToken(string $plainAccessToken, ?int $tenantId = null): bool
    {
        [$tokenKey, $tokenSecret] = $this->splitToken($plainAccessToken);
        if ($tokenKey === null || $tokenSecret === null) {
            return false;
        }

        $record = $this->accessTokens->findActiveByTokenKey($tenantId, $tokenKey);
        if ($record === null) {
            return false;
        }

        if (! $this->passwordHasher->verify($tokenSecret, (string) $record->get('token_hash', ''))) {
            return false;
        }

        $this->accessTokens->update($record->id(), [
            'status' => 'revoked',
            'revoked_at' => now(),
            'row_version' => ((int) $record->get('row_version', 1)) + 1,
        ]);

        return true;
    }

    public function revokeSessionTokens(int $sessionId, ?int $tenantId = null): void
    {
        $this->accessTokens->revokeBySessionId($sessionId, $tenantId);
        $this->refreshTokens->revokeBySessionId($sessionId, $tenantId);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitToken(string $token): array
    {
        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2) {
            return [null, null];
        }

        if ($parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }
}
