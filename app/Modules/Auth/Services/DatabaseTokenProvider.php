<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use LogicException;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Constants\AuthTokenScope;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Auth\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthPlatformAccessTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthPlatformRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthSessionRepositoryInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\DTOs\DataRecord;

final class DatabaseTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly AuthAccessTokenRepositoryInterface $tenantAccessTokens,
        private readonly AuthRefreshTokenRepositoryInterface $tenantRefreshTokens,
        private readonly AuthPlatformAccessTokenRepositoryInterface $platformAccessTokens,
        private readonly AuthPlatformRefreshTokenRepositoryInterface $platformRefreshTokens,
        private readonly AuthSessionRepositoryInterface $sessions,
        private readonly AuthPlatformSessionModel $platformSessions,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    /** @return array<string, mixed> */
    public function issue(TokenIssueData $data): array
    {
        $this->assertIssueData($data);

        $accessKey = AuthTokenKeyPrefix::accessForScope($data->tokenScope).Str::random(48);
        $accessSecret = Str::random(72);
        $refreshKey = AuthTokenKeyPrefix::refreshForScope($data->tokenScope).Str::random(48);
        $refreshSecret = Str::random(72);
        $issuedAt = now();
        $accessExpiresAt = now()->addSeconds($data->accessTokenTtlSeconds);
        $refreshExpiresAt = now()->addSeconds($data->refreshTokenTtlSeconds);

        if ($data->tokenScope === AuthTokenScope::PLATFORM) {
            $access = $this->platformAccessTokens->create([
                'platform_session_id' => $data->platformSessionId,
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
            $refresh = $this->platformRefreshTokens->create([
                'access_token_id' => (int) $access->id(),
                'platform_session_id' => $data->platformSessionId,
                'user_id' => $data->userId,
                'refresh_key' => $refreshKey,
                'refresh_hash' => $this->passwordHasher->hash($refreshSecret),
                'status' => 'active',
                'issued_at' => $issuedAt,
                'expires_at' => $refreshExpiresAt,
                'row_version' => 1,
                'metadata' => $data->metadata,
            ]);
        } else {
            $access = $this->tenantAccessTokens->create([
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
            $refresh = $this->tenantRefreshTokens->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'access_token_id' => (int) $access->id(),
                'provider_id' => $data->providerId,
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
        }

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

    /** @return array<string, mixed>|null */
    public function refresh(TokenRefreshData $data): ?array
    {
        [$refreshKey, $refreshSecret] = $this->splitToken($data->refreshToken);
        $scope = $refreshKey === null ? null : AuthTokenKeyPrefix::scopeFromRefreshKey($refreshKey);
        if ($refreshKey === null || $refreshSecret === null || $scope !== $data->tokenScope) {
            return null;
        }

        $refreshRepository = $this->refreshRepository($scope);
        $existing = $refreshRepository->findActiveByRefreshKey($refreshKey);
        if ($existing === null || ! $this->recordMatchesScope($existing, $data->tenantId, $scope)) {
            return null;
        }
        if ($scope === AuthTokenScope::PLATFORM && ! $this->platformSessionUsable(
            $this->positiveInt($existing->get('platform_session_id')),
            $this->positiveInt($existing->get('user_id')),
        )) {
            return null;
        }
        if (! $this->passwordHasher->verify($refreshSecret, (string) $existing->get('refresh_hash', ''))) {
            return null;
        }
        if ($scope === AuthTokenScope::TENANT && ! $this->tenantSessionUsable($existing, $data->tenantId)) {
            return null;
        }

        $expiresAt = $existing->get('expires_at');
        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            $refreshRepository->update($existing->id(), [
                'status' => 'expired',
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return null;
        }

        $rowVersion = (int) $existing->get('row_version', 1);
        if (! $refreshRepository->rotateIfActive((int) $existing->id(), $rowVersion)) {
            return null;
        }

        $accessRepository = $this->accessRepository($scope);
        $accessToken = $accessRepository->findById((int) $existing->get('access_token_id'));
        if ($accessToken === null || (int) $accessToken->get('user_id', 0) !== (int) $existing->get('user_id', 0)) {
            return null;
        }

        $existingScopes = $this->normalizeScopes($accessToken->get('scopes'));
        $requestedScopes = $this->normalizeScopes($data->scopes);
        $scopes = $requestedScopes === []
            ? $existingScopes
            : array_values(array_intersect($existingScopes, $requestedScopes));

        return $this->issue(TokenIssueData::fromArray([
            'tenant_id' => $scope === AuthTokenScope::TENANT ? $existing->get('tenant_id') : null,
            'organization_unit_id' => $scope === AuthTokenScope::TENANT ? $existing->get('organization_unit_id') : null,
            'provider_id' => $scope === AuthTokenScope::TENANT ? $existing->get('provider_id') : null,
            'client_id' => $scope === AuthTokenScope::TENANT ? $existing->get('client_id') : null,
            'identity_id' => $scope === AuthTokenScope::TENANT ? $existing->get('identity_id') : null,
            'session_id' => $scope === AuthTokenScope::TENANT ? $existing->get('session_id') : null,
            'platform_session_id' => $scope === AuthTokenScope::PLATFORM ? $existing->get('platform_session_id') : null,
            'user_id' => $existing->get('user_id'),
            'token_scope' => $scope,
            'grant_type' => 'refresh_token',
            'scopes' => $scopes,
            'access_token_ttl_seconds' => $data->accessTokenTtlSeconds,
            'refresh_token_ttl_seconds' => $data->refreshTokenTtlSeconds,
            'metadata' => $existing->get('metadata'),
        ]));
    }

    /** @return array<string, mixed>|null */
    public function validate(string $plainAccessToken, ?int $tenantId = null): ?array
    {
        [$tokenKey, $tokenSecret] = $this->splitToken($plainAccessToken);
        $scope = $tokenKey === null ? null : AuthTokenKeyPrefix::scopeFromAccessKey($tokenKey);
        if ($tokenKey === null || $tokenSecret === null || $scope === null) {
            return null;
        }

        $repository = $this->accessRepository($scope);
        $record = $repository->findActiveByTokenKey($tokenKey);
        if ($record === null || ! $this->recordMatchesScope($record, $tenantId, $scope)) {
            return null;
        }
        if (! $this->passwordHasher->verify($tokenSecret, (string) $record->get('token_hash', ''))) {
            return null;
        }
        if ($scope === AuthTokenScope::PLATFORM && ! $this->platformSessionUsable(
            $this->positiveInt($record->get('platform_session_id')),
            $this->positiveInt($record->get('user_id')),
        )) {
            return null;
        }

        $expiresAt = $record->get('expires_at');
        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            $repository->update($record->id(), [
                'status' => 'expired',
                'row_version' => ((int) $record->get('row_version', 1)) + 1,
            ]);

            return null;
        }

        return $this->presentTokenRecord($record, $scope);
    }

    public function revokeAccessToken(string $plainAccessToken, ?int $tenantId = null): bool
    {
        [$tokenKey, $tokenSecret] = $this->splitToken($plainAccessToken);
        $scope = $tokenKey === null ? null : AuthTokenKeyPrefix::scopeFromAccessKey($tokenKey);
        if ($tokenKey === null || $tokenSecret === null || $scope === null) {
            return false;
        }

        $repository = $this->accessRepository($scope);
        $record = $repository->findActiveByTokenKey($tokenKey);
        if ($record === null || ! $this->recordMatchesScope($record, $tenantId, $scope)) {
            return false;
        }
        if (! $this->passwordHasher->verify($tokenSecret, (string) $record->get('token_hash', ''))) {
            return false;
        }

        $repository->update($record->id(), [
            'status' => 'revoked',
            'revoked_at' => now(),
            'row_version' => ((int) $record->get('row_version', 1)) + 1,
        ]);

        return true;
    }

    public function revokeRefreshToken(string $plainRefreshToken, ?int $tenantId = null): bool
    {
        [$refreshKey, $refreshSecret] = $this->splitToken($plainRefreshToken);
        $scope = $refreshKey === null ? null : AuthTokenKeyPrefix::scopeFromRefreshKey($refreshKey);
        if ($refreshKey === null || $refreshSecret === null || $scope === null) {
            return false;
        }

        $repository = $this->refreshRepository($scope);
        $record = $repository->findActiveByRefreshKey($refreshKey);
        if ($record === null || ! $this->recordMatchesScope($record, $tenantId, $scope)) {
            return false;
        }
        if (! $this->passwordHasher->verify($refreshSecret, (string) $record->get('refresh_hash', ''))) {
            return false;
        }

        $repository->update($record->id(), [
            'status' => 'revoked',
            'revoked_at' => now(),
            'row_version' => ((int) $record->get('row_version', 1)) + 1,
        ]);

        return true;
    }

    public function revokeSessionTokens(int $sessionId, ?int $tenantId = null): void
    {
        if ($tenantId === null || $tenantId < 1) {
            throw new LogicException('Tenant session token revocation requires tenant ownership.');
        }

        $this->tenantAccessTokens->revokeBySessionId($sessionId, $tenantId);
        $this->tenantRefreshTokens->revokeBySessionId($sessionId, $tenantId);
    }

    private function assertIssueData(TokenIssueData $data): void
    {
        if ($data->userId === null || $data->userId < 1) {
            throw new LogicException('Authentication tokens require a valid user identity.');
        }

        if ($data->tokenScope === AuthTokenScope::PLATFORM) {
            foreach ([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'provider_id' => $data->providerId,
                'client_id' => $data->clientId,
                'identity_id' => $data->identityId,
                'session_id' => $data->sessionId,
            ] as $attribute => $value) {
                if ($value !== null) {
                    throw new LogicException("Platform authentication tokens cannot contain {$attribute}.");
                }
            }

            if (! $this->platformSessionUsable($data->platformSessionId, $data->userId, false)) {
                throw new LogicException('Platform session is not active.');
            }

            return;
        }

        if ($data->tenantId === null || $data->tenantId < 1) {
            throw new LogicException('Tenant authentication tokens require tenant ownership.');
        }
        if ($data->platformSessionId !== null) {
            throw new LogicException('Tenant authentication tokens cannot reference a platform session.');
        }
    }

    private function tenantSessionUsable(DataRecord $token, ?int $expectedTenantId): bool
    {
        $sessionId = $this->positiveInt($token->get('session_id'));
        if ($sessionId === null) {
            return true;
        }

        $session = $this->sessions->findById($sessionId);
        if ($session === null || (string) $session->get('status', '') !== 'active') {
            $this->tenantRefreshTokens->update($token->id(), [
                'status' => 'revoked',
                'revoked_at' => now(),
                'row_version' => ((int) $token->get('row_version', 1)) + 1,
            ]);

            return false;
        }

        $sessionTenantId = $this->positiveInt($session->get('tenant_id'));

        return $expectedTenantId === null || $sessionTenantId === $expectedTenantId;
    }

    private function recordMatchesScope(DataRecord $record, ?int $expectedTenantId, string $scope): bool
    {
        if ($scope === AuthTokenScope::PLATFORM) {
            return $expectedTenantId === null;
        }

        $recordTenantId = $this->positiveInt($record->get('tenant_id'));

        return $recordTenantId !== null
            && ($expectedTenantId === null || $recordTenantId === $expectedTenantId);
    }

    private function platformSessionUsable(?int $sessionId, ?int $userId, bool $touch = true): bool
    {
        if ($sessionId === null || $sessionId < 1 || $userId === null || $userId < 1) {
            return false;
        }

        $session = $this->platformSessions->newQuery()
            ->whereKey($sessionId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
        if (! $session instanceof AuthPlatformSessionModel) {
            return false;
        }

        $expiresAt = $session->getAttribute('expires_at');
        if ($expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt)) {
            $session->forceFill([
                'status' => 'expired',
                'row_version' => ((int) $session->getAttribute('row_version')) + 1,
            ])->save();

            return false;
        }

        if ($touch) {
            $session->forceFill(['last_activity_at' => now()])->save();
        }

        return true;
    }

    private function accessRepository(string $scope): AuthAccessTokenRepositoryInterface|AuthPlatformAccessTokenRepositoryInterface
    {
        return $scope === AuthTokenScope::PLATFORM
            ? $this->platformAccessTokens
            : $this->tenantAccessTokens;
    }

    private function refreshRepository(string $scope): AuthRefreshTokenRepositoryInterface|AuthPlatformRefreshTokenRepositoryInterface
    {
        return $scope === AuthTokenScope::PLATFORM
            ? $this->platformRefreshTokens
            : $this->tenantRefreshTokens;
    }

    /** @return array<string, mixed> */
    private function presentTokenRecord(DataRecord $record, string $scope): array
    {
        return array_merge($record->toArray(), [
            'tenant_id' => $scope === AuthTokenScope::TENANT ? $record->get('tenant_id') : null,
            'organization_unit_id' => $scope === AuthTokenScope::TENANT ? $record->get('organization_unit_id') : null,
            'platform_session_id' => $scope === AuthTokenScope::PLATFORM ? $record->get('platform_session_id') : null,
            'token_scope' => $scope,
        ]);
    }

    /** @return list<string> */
    private function normalizeScopes(mixed $scopes): array
    {
        if (! is_array($scopes)) {
            return [];
        }

        $normalized = [];
        foreach ($scopes as $scope) {
            if (! is_string($scope)) {
                continue;
            }

            $scope = trim($scope);
            if ($scope !== '') {
                $normalized[$scope] = $scope;
            }
        }

        return array_values($normalized);
    }

    /** @return array{0: string|null, 1: string|null} */
    private function splitToken(string $token): array
    {
        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }
}
