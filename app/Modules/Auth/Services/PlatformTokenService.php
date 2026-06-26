<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Enums\AuthScope;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthPlatformAccessTokenModel;
use Modules\Auth\Models\AuthPlatformRefreshTokenModel;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Auth\Services\Security\TokenValueParser;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;

final readonly class PlatformTokenService
{
    public function __construct(
        private DatabaseManager $database,
        private OpaqueTokenCodec $codec,
        private AuthSecurityConfig $config,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private PlatformOperatorAuthenticationDirectoryInterface $platformOperators,
        private TokenValueParser $values,
    ) {}

    public function issueSessionTokens(int $sessionId, int $operatorId): array
    {
        return $this->runAsControlPlane(function () use ($sessionId, $operatorId): array {
            return $this->database->transaction(function () use ($sessionId, $operatorId): array {
                $session = AuthPlatformSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('platform_operator_id', $operatorId)
                    ->lockForUpdate()
                    ->first();

                if (! $session instanceof AuthPlatformSessionModel) {
                    throw $this->invalidSession();
                }

                $this->assertPlatformSessionActive($session);

                return $this->createPlatformPair(
                    $session,
                    ['platform'],
                    GrantType::PLATFORM_PASSWORD,
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
        $parsed = $this->codec->parse($plainRefreshToken, AuthTokenKeyPrefix::PLATFORM_REFRESH);
        if ($parsed === null) {
            throw $this->invalidToken();
        }

        return $this->runAsControlPlane(function () use ($key, $parsed): array {
            return $this->database->transaction(function () use ($key, $parsed): array {
                $refresh = AuthPlatformRefreshTokenModel::query()
                    ->where('refresh_key', $key)
                    ->lockForUpdate()
                    ->first();

                if (! $refresh instanceof AuthPlatformRefreshTokenModel
                    || ! hash_equals((string) $refresh->getAttribute('refresh_digest'), $parsed['digest'])) {
                    throw $this->invalidToken();
                }

                if ((string) $refresh->getAttribute('status') !== TokenStatus::ACTIVE->value) {
                    $this->compromisePlatformFamily($refresh, 'refresh_token_reuse_detected');
                    throw new AuthFailure(
                        AuthErrorCode::TOKEN_REVOKED,
                        'The refresh session is no longer valid.',
                        401,
                    );
                }

                if ($this->values->isExpired($refresh->getAttribute('expires_at'))) {
                    $this->expirePlatformRefresh($refresh);
                    throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The refresh session has expired.', 401);
                }

                $session = AuthPlatformSessionModel::query()
                    ->whereKey((int) $refresh->getAttribute('platform_session_id'))
                    ->where('platform_operator_id', (int) $refresh->getAttribute('platform_operator_id'))
                    ->lockForUpdate()
                    ->first();

                if (! $session instanceof AuthPlatformSessionModel) {
                    $this->compromisePlatformFamily($refresh, 'refresh_session_graph_invalid');
                    throw $this->invalidSession();
                }

                $this->assertPlatformSessionActive($session);
                $operatorId = (int) $session->getAttribute('platform_operator_id');
                if ($this->platformOperators->findActivePlatformById($operatorId) === null) {
                    $this->revokePlatformSessionLocked($session, 'platform_operator_inactive');
                    throw $this->invalidToken();
                }

                $access = AuthPlatformAccessTokenModel::query()
                    ->whereKey((int) $refresh->getAttribute('access_token_id'))
                    ->where('platform_session_id', (int) $session->getKey())
                    ->where('platform_operator_id', $operatorId)
                    ->lockForUpdate()
                    ->first();

                if (! $access instanceof AuthPlatformAccessTokenModel) {
                    $this->compromisePlatformFamily($refresh, 'refresh_access_graph_invalid');
                    throw $this->invalidToken();
                }

                $now = $this->clock->now();
                $refresh->forceFill([
                    'status' => TokenStatus::ROTATED->value,
                    'rotated_at' => $now,
                    'row_version' => (int) $refresh->getAttribute('row_version') + 1,
                ])->save();
                $this->revokePlatformAccessTokenModel($access, 'refresh_rotated');

                return $this->createPlatformPair(
                    $session,
                    ['platform'],
                    GrantType::REFRESH_TOKEN,
                    (string) $refresh->getAttribute('family_id'),
                    (int) $refresh->getKey(),
                );
            }, 3);
        });
    }

    public function revokeSession(int $sessionId, int $operatorId, string $reason): void
    {
        $this->runAsControlPlane(function () use ($sessionId, $operatorId, $reason): void {
            $this->database->transaction(function () use ($sessionId, $operatorId, $reason): void {
                $session = AuthPlatformSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('platform_operator_id', $operatorId)
                    ->lockForUpdate()
                    ->first();

                if ($session instanceof AuthPlatformSessionModel) {
                    $this->revokePlatformSessionLocked($session, $reason);
                }
            }, 3);
        });
    }

    /** @return array<string, mixed> */
    public function validateAccessToken(string $plainToken): array
    {
        $plainToken = trim($plainToken);
        $key = $this->values->tokenKey($plainToken);
        if (! str_starts_with($key, AuthTokenKeyPrefix::PLATFORM_ACCESS)) {
            throw $this->invalidToken();
        }

        return $this->validatePlatformAccessToken($plainToken, $key);
    }


    private function validatePlatformAccessToken(string $plainToken, string $key): array
    {
        $parsed = $this->codec->parse($plainToken, AuthTokenKeyPrefix::PLATFORM_ACCESS);
        if ($parsed === null) {
            throw $this->invalidToken();
        }

        return $this->runAsControlPlane(function () use ($key, $parsed): array {
            $token = AuthPlatformAccessTokenModel::query()->where('token_key', $key)->first();
            if (! $token instanceof AuthPlatformAccessTokenModel
                || ! hash_equals((string) $token->getAttribute('token_digest'), $parsed['digest'])) {
                throw $this->invalidToken();
            }

            $this->assertPlatformAccessTokenActive($token);
            $session = AuthPlatformSessionModel::query()
                ->whereKey((int) $token->getAttribute('platform_session_id'))
                ->where('platform_operator_id', (int) $token->getAttribute('platform_operator_id'))
                ->first();

            if (! $session instanceof AuthPlatformSessionModel) {
                throw $this->invalidSession();
            }

            $this->assertPlatformSessionActive($session);
            $operatorId = (int) $session->getAttribute('platform_operator_id');
            if ($this->platformOperators->findActivePlatformById($operatorId) === null) {
                throw $this->invalidToken();
            }

            $this->touchPlatformSession($session);

            return [
                'token_scope' => AuthScope::PLATFORM->value,
                'platform_operator_id' => $operatorId,
                'session_id' => (int) $session->getKey(),
                'session_public_id' => (string) $session->getAttribute('public_id'),
                'application_id' => 'platform',
                'scopes' => ['platform'],
                'grant_type' => (string) $token->getAttribute('grant_type'),
                'authenticated_at' => $this->values->atom($session->getAttribute('authenticated_at')),
                'mfa_verified_at' => $this->values->atom($session->getAttribute('mfa_verified_at')),
                'issued_at' => $this->values->atom($token->getAttribute('issued_at')),
                'expires_at' => $this->values->atom($token->getAttribute('expires_at')),
            ];
        });
    }

    private function createPlatformPair(
        AuthPlatformSessionModel $session,
        array $scopes,
        GrantType $grantType,
        string $familyId,
        ?int $parentRefreshTokenId,
    ): array {
        $now = $this->clock->now();
        $sessionExpiresAt = $session->getAttribute('expires_at');
        $accessExpiresAt = $now->modify('+'.$this->config->accessTokenTtlSeconds.' seconds');
        if ($this->values->timestamp($sessionExpiresAt) < $accessExpiresAt->getTimestamp()) {
            $accessExpiresAt = new \DateTimeImmutable($sessionExpiresAt->format(DATE_ATOM));
        }

        $refreshExpiresAt = $now->modify('+'.$this->config->refreshTokenTtlSeconds.' seconds');
        if ($this->values->timestamp($sessionExpiresAt) < $refreshExpiresAt->getTimestamp()) {
            $refreshExpiresAt = new \DateTimeImmutable($sessionExpiresAt->format(DATE_ATOM));
        }

        $accessSecret = $this->codec->issue(AuthTokenKeyPrefix::PLATFORM_ACCESS);
        $access = AuthPlatformAccessTokenModel::query()->create([
            'platform_session_id' => (int) $session->getKey(),
            'platform_operator_id' => (int) $session->getAttribute('platform_operator_id'),
            'token_key' => $accessSecret['key'],
            'token_digest' => $accessSecret['digest'],
            'scopes' => $scopes,
            'grant_type' => $grantType->value,
            'status' => TokenStatus::ACTIVE->value,
            'issued_at' => $now,
            'expires_at' => $accessExpiresAt,
            'row_version' => 1,
        ]);

        $refreshSecret = $this->codec->issue(AuthTokenKeyPrefix::PLATFORM_REFRESH);
        AuthPlatformRefreshTokenModel::query()->create([
            'access_token_id' => (int) $access->getKey(),
            'parent_refresh_token_id' => $parentRefreshTokenId,
            'family_id' => $familyId,
            'platform_session_id' => (int) $session->getKey(),
            'platform_operator_id' => (int) $session->getAttribute('platform_operator_id'),
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

    private function assertPlatformAccessTokenActive(AuthPlatformAccessTokenModel $token): void
    {
        $status = (string) $token->getAttribute('status');
        if ($status !== TokenStatus::ACTIVE->value) {
            throw new AuthFailure(AuthErrorCode::TOKEN_REVOKED, 'The access token is no longer active.', 401);
        }
        if ($this->values->isExpired($token->getAttribute('expires_at'))) {
            throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The access token has expired.', 401);
        }
    }

    private function assertPlatformSessionActive(AuthPlatformSessionModel $session): void
    {
        if ((string) $session->getAttribute('status') !== SessionStatus::ACTIVE->value) {
            throw $this->invalidSession();
        }
        if ($this->values->isExpired($session->getAttribute('expires_at'))) {
            throw new AuthFailure(AuthErrorCode::TOKEN_EXPIRED, 'The authentication session has expired.', 401);
        }
    }

    private function revokePlatformSessionLocked(AuthPlatformSessionModel $session, string $reason): void
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

        foreach (AuthPlatformAccessTokenModel::query()
            ->where('platform_session_id', $session->getKey())
            ->where('status', TokenStatus::ACTIVE->value)
            ->lockForUpdate()
            ->get() as $access) {
            $this->revokePlatformAccessTokenModel($access, $reason);
        }

        foreach (AuthPlatformRefreshTokenModel::query()
            ->where('platform_session_id', $session->getKey())
            ->whereIn('status', [TokenStatus::ACTIVE->value, TokenStatus::ROTATED->value])
            ->lockForUpdate()
            ->get() as $refresh) {
            $this->revokePlatformRefreshTokenModel($refresh, $reason);
        }
    }

    private function compromisePlatformFamily(AuthPlatformRefreshTokenModel $refresh, string $reason): void
    {
        $familyId = (string) $refresh->getAttribute('family_id');
        foreach (AuthPlatformRefreshTokenModel::query()
            ->where('family_id', $familyId)
            ->lockForUpdate()
            ->get() as $familyToken) {
            $this->revokePlatformRefreshTokenModel($familyToken, $reason);
        }

        $session = AuthPlatformSessionModel::query()
            ->whereKey((int) $refresh->getAttribute('platform_session_id'))
            ->lockForUpdate()
            ->first();
        if ($session instanceof AuthPlatformSessionModel) {
            $this->revokePlatformSessionLocked($session, $reason);
        }
    }

    private function expirePlatformRefresh(AuthPlatformRefreshTokenModel $refresh): void
    {
        $refresh->forceFill([
            'status' => TokenStatus::EXPIRED->value,
            'row_version' => (int) $refresh->getAttribute('row_version') + 1,
        ])->save();
    }

    private function revokePlatformAccessTokenModel(AuthPlatformAccessTokenModel $token, string $reason): void
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

    private function revokePlatformRefreshTokenModel(AuthPlatformRefreshTokenModel $token, string $reason): void
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

    private function touchPlatformSession(AuthPlatformSessionModel $session): void
    {
        $lastActivity = $this->values->timestamp($session->getAttribute('last_activity_at'));
        $now = $this->clock->now();
        if (($now->getTimestamp() - $lastActivity) < $this->config->activityTouchIntervalSeconds) {
            return;
        }

        AuthPlatformSessionModel::query()
            ->whereKey($session->getKey())
            ->where('status', SessionStatus::ACTIVE->value)
            ->where('last_activity_at', '<=', $now->modify('-'.$this->config->activityTouchIntervalSeconds.' seconds'))
            ->increment('row_version', 1, [
                'last_activity_at' => $now,
                'updated_at' => $now,
            ]);
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
