<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Enums\AuthScope;
use Modules\Auth\Enums\ClientStatus;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthClientModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Auth\Services\Security\TokenValueParser;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;

final readonly class TenantTokenService
{
    public function __construct(
        private DatabaseManager $database,
        private OpaqueTokenCodec $codec,
        private AuthSecurityConfig $config,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private TenantUserAuthenticationDirectoryInterface $tenantUsers,
        private TokenValueParser $values,
    ) {}

    public function issueSessionTokens(
        int $tenantId,
        int $sessionId,
        int $userId,
        ?int $clientId,
        array $scopes,
        GrantType $grantType,
    ): array {
        return $this->runForTenant($tenantId, function () use (
            $tenantId,
            $sessionId,
            $userId,
            $clientId,
            $scopes,
            $grantType,
        ): array {
            return $this->database->transaction(function () use (
                $tenantId,
                $sessionId,
                $userId,
                $clientId,
                $scopes,
                $grantType,
            ): array {
                $session = AuthSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (! $session instanceof AuthSessionModel) {
                    throw $this->invalidSession();
                }

                $this->assertTenantSessionActive($session);
                $client = $this->loadTenantClient($tenantId, $clientId, $grantType);

                return $this->createTenantPair(
                    $session,
                    $client,
                    $this->normalizeTenantScopes($scopes),
                    $grantType,
                    (string) Str::uuid(),
                    null,
                );
            }, 3);
        });
    }

    public function refresh(string $plainRefreshToken): array
    {
        $plainRefreshToken = trim($plainRefreshToken);
        $key = $this->values->tokenKey($plainRefreshToken);
        $parsed = $this->codec->parse($plainRefreshToken, AuthTokenKeyPrefix::TENANT_REFRESH);
        if ($parsed === null) {
            throw $this->invalidToken();
        }

        $locator = $this->findTenantRefreshLocator($key);
        if ($locator === null) {
            throw $this->invalidToken();
        }

        return $this->runForTenant($locator['tenant_id'], function () use ($locator, $parsed): array {
            return $this->database->transaction(function () use ($locator, $parsed): array {
                $refresh = AuthRefreshTokenModel::query()
                    ->whereKey($locator['id'])
                    ->where('tenant_id', $locator['tenant_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $refresh instanceof AuthRefreshTokenModel
                    || ! hash_equals((string) $refresh->getAttribute('refresh_digest'), $parsed['digest'])) {
                    throw $this->invalidToken();
                }

                if ((string) $refresh->getAttribute('status') !== TokenStatus::ACTIVE->value) {
                    $this->compromiseTenantFamily($refresh, 'refresh_token_reuse_detected');
                    throw new AuthFailure(
                        AuthErrorCode::TOKEN_REVOKED,
                        'The refresh session is no longer valid.',
                        401,
                    );
                }

                if ($this->values->isExpired($refresh->getAttribute('expires_at'))) {
                    $this->expireTenantRefresh($refresh);
                    throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The refresh session has expired.', 401);
                }

                $session = AuthSessionModel::query()
                    ->whereKey((int) $refresh->getAttribute('session_id'))
                    ->where('tenant_id', (int) $refresh->getAttribute('tenant_id'))
                    ->where('user_id', (int) $refresh->getAttribute('user_id'))
                    ->lockForUpdate()
                    ->first();

                if (! $session instanceof AuthSessionModel) {
                    $this->compromiseTenantFamily($refresh, 'refresh_session_graph_invalid');
                    throw $this->invalidSession();
                }

                $this->assertTenantSessionActive($session);
                $tenantId = (int) $session->getAttribute('tenant_id');
                $userId = (int) $session->getAttribute('user_id');
                if ($this->tenantUsers->findActiveTenantById($tenantId, $userId) === null) {
                    $this->revokeTenantSessionLocked($session, 'tenant_user_inactive');
                    throw $this->invalidToken();
                }

                $access = AuthAccessTokenModel::query()
                    ->whereKey((int) $refresh->getAttribute('access_token_id'))
                    ->where('tenant_id', $tenantId)
                    ->where('session_id', (int) $session->getKey())
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (! $access instanceof AuthAccessTokenModel) {
                    $this->compromiseTenantFamily($refresh, 'refresh_access_graph_invalid');
                    throw $this->invalidToken();
                }

                $client = $this->loadTenantClient(
                    $tenantId,
                    $this->values->positiveInt($refresh->getAttribute('client_id')),
                    GrantType::REFRESH_TOKEN,
                );

                $now = $this->clock->now();
                $refresh->forceFill([
                    'status' => TokenStatus::ROTATED->value,
                    'rotated_at' => $now,
                    'row_version' => (int) $refresh->getAttribute('row_version') + 1,
                ])->save();
                $this->revokeAccessTokenModel($access, 'refresh_rotated');

                return $this->createTenantPair(
                    $session,
                    $client,
                    $this->normalizeTenantScopes($access->getAttribute('scopes')),
                    GrantType::REFRESH_TOKEN,
                    (string) $refresh->getAttribute('family_id'),
                    (int) $refresh->getKey(),
                );
            }, 3);
        });
    }

    public function revokeSession(int $tenantId, int $sessionId, int $userId, string $reason): void
    {
        $this->runForTenant($tenantId, function () use ($tenantId, $sessionId, $userId, $reason): void {
            $this->database->transaction(function () use ($tenantId, $sessionId, $userId, $reason): void {
                $session = AuthSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($session instanceof AuthSessionModel) {
                    $this->revokeTenantSessionLocked($session, $reason);
                }
            }, 3);
        });
    }

    /** @return array<string, mixed> */
    public function validateAccessToken(string $plainToken): array
    {
        $plainToken = trim($plainToken);
        $key = $this->values->tokenKey($plainToken);
        if (! str_starts_with($key, AuthTokenKeyPrefix::TENANT_ACCESS)) {
            throw $this->invalidToken();
        }

        return $this->validateTenantAccessToken($plainToken, $key);
    }


    private function validateTenantAccessToken(string $plainToken, string $key): array
    {
        $parsed = $this->codec->parse($plainToken, AuthTokenKeyPrefix::TENANT_ACCESS);
        $locator = $this->findTenantAccessLocator($key);
        if ($parsed === null || $locator === null) {
            throw $this->invalidToken();
        }

        return $this->runForTenant($locator['tenant_id'], function () use ($locator, $parsed): array {
            $token = AuthAccessTokenModel::query()
                ->whereKey($locator['id'])
                ->where('tenant_id', $locator['tenant_id'])
                ->first();

            if (! $token instanceof AuthAccessTokenModel
                || ! hash_equals((string) $token->getAttribute('token_digest'), $parsed['digest'])) {
                throw $this->invalidToken();
            }

            $this->assertAccessTokenActive($token);
            $session = AuthSessionModel::query()
                ->whereKey((int) $token->getAttribute('session_id'))
                ->where('tenant_id', (int) $token->getAttribute('tenant_id'))
                ->where('user_id', (int) $token->getAttribute('user_id'))
                ->first();

            if (! $session instanceof AuthSessionModel) {
                throw $this->invalidSession();
            }

            $this->assertTenantSessionActive($session);
            $tenantId = (int) $session->getAttribute('tenant_id');
            $userId = (int) $session->getAttribute('user_id');
            if ($this->tenantUsers->findActiveTenantById($tenantId, $userId) === null) {
                throw $this->invalidToken();
            }

            $clientId = $this->values->positiveInt($token->getAttribute('client_id'));
            if ($clientId !== null) {
                $this->loadTenantClient($tenantId, $clientId, null);
            }

            $this->touchTenantSession($session);

            return [
                'token_scope' => AuthScope::TENANT->value,
                'tenant_id' => $tenantId,
                'tenant_user_id' => $userId,
                'session_id' => (int) $session->getKey(),
                'session_public_id' => (string) $session->getAttribute('public_id'),
                'organization_unit_id' => $this->values->positiveInt($session->getAttribute('organization_unit_id')),
                'provider_id' => (int) $session->getAttribute('provider_id'),
                'identity_id' => (int) $session->getAttribute('identity_id'),
                'client_id' => $clientId,
                'application_id' => $clientId === null ? 'tenant' : 'oauth',
                'scopes' => $this->normalizeTenantScopes($token->getAttribute('scopes')),
                'grant_type' => (string) $token->getAttribute('grant_type'),
                'issued_at' => $this->values->atom($token->getAttribute('issued_at')),
                'expires_at' => $this->values->atom($token->getAttribute('expires_at')),
            ];
        });
    }

    private function createTenantPair(
        AuthSessionModel $session,
        ?AuthClientModel $client,
        array $scopes,
        GrantType $grantType,
        string $familyId,
        ?int $parentRefreshTokenId,
    ): array {
        $now = $this->clock->now();
        $accessExpiresAt = $now->modify('+'.$this->config->accessTokenTtlSeconds.' seconds');
        $sessionExpiresAt = $session->getAttribute('expires_at');
        if ($this->values->timestamp($sessionExpiresAt) < $accessExpiresAt->getTimestamp()) {
            $accessExpiresAt = new \DateTimeImmutable($sessionExpiresAt->format(DATE_ATOM));
        }

        $refreshExpiresAt = $now->modify('+'.$this->config->refreshTokenTtlSeconds.' seconds');
        if ($this->values->timestamp($sessionExpiresAt) < $refreshExpiresAt->getTimestamp()) {
            $refreshExpiresAt = new \DateTimeImmutable($sessionExpiresAt->format(DATE_ATOM));
        }

        $accessSecret = $this->codec->issue(AuthTokenKeyPrefix::TENANT_ACCESS);
        $access = AuthAccessTokenModel::query()->create([
            'tenant_id' => (int) $session->getAttribute('tenant_id'),
            'session_id' => (int) $session->getKey(),
            'user_id' => (int) $session->getAttribute('user_id'),
            'client_id' => $client?->getKey(),
            'token_key' => $accessSecret['key'],
            'token_digest' => $accessSecret['digest'],
            'scopes' => $scopes,
            'grant_type' => $grantType->value,
            'status' => TokenStatus::ACTIVE->value,
            'issued_at' => $now,
            'expires_at' => $accessExpiresAt,
            'row_version' => 1,
        ]);

        $refreshSecret = $this->codec->issue(AuthTokenKeyPrefix::TENANT_REFRESH);
        AuthRefreshTokenModel::query()->create([
            'tenant_id' => (int) $session->getAttribute('tenant_id'),
            'access_token_id' => (int) $access->getKey(),
            'parent_refresh_token_id' => $parentRefreshTokenId,
            'family_id' => $familyId,
            'session_id' => (int) $session->getKey(),
            'user_id' => (int) $session->getAttribute('user_id'),
            'client_id' => $client?->getKey(),
            'refresh_key' => $refreshSecret['key'],
            'refresh_digest' => $refreshSecret['digest'],
            'status' => TokenStatus::ACTIVE->value,
            'issued_at' => $now,
            'expires_at' => $refreshExpiresAt,
            'row_version' => 1,
        ]);

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessSecret['plain'],
            'access_token_expires_at' => $accessExpiresAt->format(DATE_ATOM),
            'refresh_token' => $refreshSecret['plain'],
            'refresh_token_expires_at' => $refreshExpiresAt->format(DATE_ATOM),
            'scopes' => $scopes,
        ];
    }

    private function assertAccessTokenActive(AuthAccessTokenModel $token): void
    {
        $status = (string) $token->getAttribute('status');
        if ($status !== TokenStatus::ACTIVE->value) {
            throw new AuthFailure(AuthErrorCode::TOKEN_REVOKED, 'The access token is no longer active.', 401);
        }
        if ($this->values->isExpired($token->getAttribute('expires_at'))) {
            throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The access token has expired.', 401);
        }
    }

    private function assertTenantSessionActive(AuthSessionModel $session): void
    {
        if ((string) $session->getAttribute('status') !== SessionStatus::ACTIVE->value) {
            throw $this->invalidSession();
        }
        if ($this->values->isExpired($session->getAttribute('expires_at'))) {
            throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The authentication session has expired.', 401);
        }
    }

    private function loadTenantClient(
        int $tenantId,
        ?int $clientId,
        ?GrantType $requiredGrant,
    ): ?AuthClientModel {
        if ($clientId === null) {
            return null;
        }

        $client = AuthClientModel::query()
            ->whereKey($clientId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $client instanceof AuthClientModel
            || (string) $client->getAttribute('status') !== ClientStatus::ACTIVE->value
            || ($client->getAttribute('expires_at') !== null && $this->values->isExpired($client->getAttribute('expires_at')))
        ) {
            throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'The OAuth client is unavailable.', 401);
        }

        if ($requiredGrant !== null) {
            $allowedGrants = $this->stringList($client->getAttribute('allowed_grant_types'));
            if (! in_array($requiredGrant->value, $allowedGrants, true)) {
                throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'The OAuth grant is not allowed.', 403);
            }
        }

        return $client;
    }

    private function revokeTenantSessionLocked(AuthSessionModel $session, string $reason): void
    {
        $now = $this->clock->now();
        if ((string) $session->getAttribute('status') === SessionStatus::ACTIVE->value) {
            $session->forceFill([
                'status' => SessionStatus::REVOKED->value,
                'revoked_at' => $now,
                'revocation_reason' => mb_substr(trim($reason), 0, 255),
                'row_version' => (int) $session->getAttribute('row_version') + 1,
            ])->save();
        }

        foreach (AuthAccessTokenModel::query()
            ->where('session_id', $session->getKey())
            ->where('status', TokenStatus::ACTIVE->value)
            ->lockForUpdate()
            ->get() as $access) {
            $this->revokeAccessTokenModel($access, $reason);
        }

        foreach (AuthRefreshTokenModel::query()
            ->where('session_id', $session->getKey())
            ->whereIn('status', [TokenStatus::ACTIVE->value, TokenStatus::ROTATED->value])
            ->lockForUpdate()
            ->get() as $refresh) {
            $this->revokeRefreshTokenModel($refresh, $reason);
        }
    }

    private function compromiseTenantFamily(AuthRefreshTokenModel $refresh, string $reason): void
    {
        $familyId = (string) $refresh->getAttribute('family_id');
        foreach (AuthRefreshTokenModel::query()
            ->where('family_id', $familyId)
            ->lockForUpdate()
            ->get() as $familyToken) {
            $this->revokeRefreshTokenModel($familyToken, $reason);
        }

        $session = AuthSessionModel::query()
            ->whereKey((int) $refresh->getAttribute('session_id'))
            ->lockForUpdate()
            ->first();
        if ($session instanceof AuthSessionModel) {
            $this->revokeTenantSessionLocked($session, $reason);
        }
    }

    private function expireTenantRefresh(AuthRefreshTokenModel $refresh): void
    {
        $refresh->forceFill([
            'status' => TokenStatus::EXPIRED->value,
            'row_version' => (int) $refresh->getAttribute('row_version') + 1,
        ])->save();
    }

    private function revokeAccessTokenModel(AuthAccessTokenModel $token, string $reason): void
    {
        if ((string) $token->getAttribute('status') !== TokenStatus::ACTIVE->value) {
            return;
        }

        $token->forceFill([
            'status' => TokenStatus::REVOKED->value,
            'revoked_at' => $this->clock->now(),
            'revocation_reason' => mb_substr(trim($reason), 0, 255),
            'row_version' => (int) $token->getAttribute('row_version') + 1,
        ])->save();
    }

    private function revokeRefreshTokenModel(AuthRefreshTokenModel $token, string $reason): void
    {
        if (in_array((string) $token->getAttribute('status'), [TokenStatus::REVOKED->value, TokenStatus::EXPIRED->value], true)) {
            return;
        }

        $token->forceFill([
            'status' => TokenStatus::REVOKED->value,
            'revoked_at' => $this->clock->now(),
            'revocation_reason' => mb_substr(trim($reason), 0, 255),
            'row_version' => (int) $token->getAttribute('row_version') + 1,
        ])->save();
    }

    private function touchTenantSession(AuthSessionModel $session): void
    {
        $lastActivity = $this->values->timestamp($session->getAttribute('last_activity_at'));
        $now = $this->clock->now();
        if (($now->getTimestamp() - $lastActivity) < $this->config->activityTouchIntervalSeconds) {
            return;
        }

        AuthSessionModel::query()
            ->whereKey($session->getKey())
            ->where('status', SessionStatus::ACTIVE->value)
            ->where('last_activity_at', '<=', $now->modify('-'.$this->config->activityTouchIntervalSeconds.' seconds'))
            ->increment('row_version', 1, [
                'last_activity_at' => $now,
                'updated_at' => $now,
            ]);
    }

    private function findTenantAccessLocator(string $key): ?array
    {
        return $this->runAsControlPlane(function () use ($key): ?array {
            $record = AuthAccessTokenModel::query()
                ->where('token_key', $key)
                ->first(['id', 'tenant_id']);

            return $record instanceof AuthAccessTokenModel
                ? ['id' => (int) $record->getKey(), 'tenant_id' => (int) $record->getAttribute('tenant_id')]
                : null;
        });
    }

    private function findTenantRefreshLocator(string $key): ?array
    {
        return $this->runAsControlPlane(function () use ($key): ?array {
            $record = AuthRefreshTokenModel::query()
                ->where('refresh_key', $key)
                ->first(['id', 'tenant_id']);

            return $record instanceof AuthRefreshTokenModel
                ? ['id' => (int) $record->getKey(), 'tenant_id' => (int) $record->getAttribute('tenant_id')]
                : null;
        });
    }

    private function normalizeTenantScopes(mixed $value): array
    {
        $scopes = $this->stringList($value);
        if ($scopes === []) {
            throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'At least one OAuth scope is required.', 403);
        }

        foreach ($scopes as $scope) {
            if (! in_array($scope, $this->config->oauthScopes, true)) {
                throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'An OAuth scope is not registered.', 403);
            }
        }

        return $scopes;
    }

    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }






    private function runForTenant(int $tenantId, callable $callback): mixed
    {
        $activeTenantId = $this->executionContext->tenantId();
        if ($activeTenantId !== null) {
            if ($activeTenantId !== $tenantId) {
                throw new AuthFailure(AuthErrorCode::TENANT_MISMATCH, 'Authentication tenant does not match the active workspace.', 403);
            }

            return $callback();
        }

        return $this->executionContext->runForTenant($tenantId, $callback);
    }

    private function runAsControlPlane(callable $callback): mixed
    {
        if ($this->executionContext->isControlPlane()) {
            return $callback();
        }

        if ($this->executionContext->isActive()) {
            return $callback();
        }

        return $this->executionContext->runAsControlPlane($callback);
    }

    private function invalidToken(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::TOKEN_INVALID, 'The authentication token is invalid.', 401);
    }

    private function invalidSession(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::SESSION_NOT_FOUND, 'The authentication session is unavailable.', 401);
    }
}
