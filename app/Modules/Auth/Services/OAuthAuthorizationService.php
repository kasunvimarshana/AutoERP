<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Enums\AuthorizationCodeStatus;
use Modules\Auth\Enums\ClientStatus;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthClientModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final readonly class OAuthAuthorizationService
{
    public function __construct(
        private DatabaseManager $database,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private OpaqueTokenCodec $codec,
        private PasswordHasherInterface $passwords,
        private AuthSecurityConfig $config,
        private TenantTokenService $tokens,
    ) {}

    /**
     * @param list<string> $requestedScopes
     * @return array{authorization_code:string,expires_at:string,state:?string}
     */
    public function authorize(
        int $tenantId,
        int $userId,
        int $sessionId,
        string $clientKey,
        string $redirectUri,
        array $requestedScopes,
        string $codeChallenge,
        ?string $state = null,
    ): array {
        return $this->executionContext->runForTenant($tenantId, fn (): array => $this->database->transaction(
            function () use ($tenantId, $userId, $sessionId, $clientKey, $redirectUri, $requestedScopes, $codeChallenge, $state): array {
                $session = AuthSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();
                $this->assertActiveSession($session);

                $client = AuthClientModel::query()
                    ->where('client_key', trim($clientKey))
                    ->lockForUpdate()
                    ->first();
                $this->assertClientAvailable($client, GrantType::AUTHORIZATION_CODE);
                $this->assertExactRedirect($client, $redirectUri);
                $scopes = $this->authorizeScopes($client, $requestedScopes);

                $issued = $this->codec->issue(AuthTokenKeyPrefix::AUTHORIZATION_CODE);
                $expiresAt = $this->clock->now()->modify('+'.$this->config->authorizationCodeTtlSeconds.' seconds');
                AuthAuthorizationCodeModel::query()->create([
                    'tenant_id' => $tenantId,
                    'client_id' => (int) $client->getKey(),
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'code_key' => $issued['key'],
                    'code_digest' => $issued['digest'],
                    'scopes' => $scopes,
                    'code_challenge' => $codeChallenge,
                    'redirect_uri' => $redirectUri,
                    'status' => AuthorizationCodeStatus::ACTIVE->value,
                    'issued_at' => $this->clock->now(),
                    'expires_at' => $expiresAt,
                    'row_version' => 1,
                ]);

                return [
                    'authorization_code' => $issued['plain'],
                    'expires_at' => $expiresAt->format(DATE_ATOM),
                    'state' => $state,
                ];
            },
            3,
        ));
    }

    /** @return array<string,mixed> */
    public function exchange(
        string $plainCode,
        string $clientKey,
        ?string $clientSecret,
        string $redirectUri,
        string $codeVerifier,
    ): array {
        $parsed = $this->codec->parse($plainCode, AuthTokenKeyPrefix::AUTHORIZATION_CODE);
        if ($parsed === null) {
            throw $this->invalidCode();
        }

        $locator = $this->executionContext->runAsControlPlane(function () use ($parsed): ?array {
            $code = AuthAuthorizationCodeModel::withoutGlobalScopes()
                ->where('code_key', $parsed['key'])
                ->first(['id', 'tenant_id']);
            return $code instanceof AuthAuthorizationCodeModel
                ? ['id' => (int) $code->getKey(), 'tenant_id' => (int) $code->getAttribute('tenant_id')]
                : null;
        });
        if ($locator === null) {
            throw $this->invalidCode();
        }

        return $this->executionContext->runForTenant($locator['tenant_id'], fn (): array => $this->database->transaction(
            function () use ($locator, $parsed, $clientKey, $clientSecret, $redirectUri, $codeVerifier): array {
                $code = AuthAuthorizationCodeModel::query()
                    ->whereKey($locator['id'])
                    ->lockForUpdate()
                    ->first();
                if (! $code instanceof AuthAuthorizationCodeModel
                    || ! hash_equals((string) $code->getAttribute('code_digest'), $parsed['digest'])
                    || (string) $code->getAttribute('status') !== AuthorizationCodeStatus::ACTIVE->value) {
                    throw $this->invalidCode();
                }

                if ($this->expired($code->getAttribute('expires_at'))) {
                    $code->forceFill([
                        'status' => AuthorizationCodeStatus::EXPIRED->value,
                        'row_version' => (int) $code->getAttribute('row_version') + 1,
                    ])->save();
                    throw $this->invalidCode();
                }

                $client = AuthClientModel::query()
                    ->whereKey($code->getAttribute('client_id'))
                    ->where('client_key', trim($clientKey))
                    ->lockForUpdate()
                    ->first();
                $this->assertClientAvailable($client, GrantType::AUTHORIZATION_CODE);
                $this->assertExactRedirect($client, $redirectUri);
                $this->assertClientSecret($client, $clientSecret);
                $this->assertPkce((string) $code->getAttribute('code_challenge'), $codeVerifier);

                $session = AuthSessionModel::query()
                    ->whereKey($code->getAttribute('session_id'))
                    ->where('user_id', $code->getAttribute('user_id'))
                    ->lockForUpdate()
                    ->first();
                $this->assertActiveSession($session);

                $code->forceFill([
                    'status' => AuthorizationCodeStatus::CONSUMED->value,
                    'consumed_at' => $this->clock->now(),
                    'row_version' => (int) $code->getAttribute('row_version') + 1,
                ])->save();

                return $this->tokens->issueSessionTokens(
                    (int) $code->getAttribute('tenant_id'),
                    (int) $session->getKey(),
                    (int) $session->getAttribute('user_id'),
                    (int) $client->getKey(),
                    $this->stringList($code->getAttribute('scopes')),
                    GrantType::AUTHORIZATION_CODE,
                );
            },
            3,
        ));
    }

    private function assertActiveSession(mixed $session): void
    {
        if (! $session instanceof AuthSessionModel
            || (string) $session->getAttribute('status') !== SessionStatus::ACTIVE->value
            || $this->expired($session->getAttribute('expires_at'))) {
            throw new AuthFailure(AuthErrorCode::SESSION_NOT_FOUND, 'The authentication session is unavailable.', 401);
        }
    }

    private function assertClientAvailable(mixed $client, GrantType $requiredGrant): void
    {
        if (! $client instanceof AuthClientModel
            || (string) $client->getAttribute('status') !== ClientStatus::ACTIVE->value
            || ($client->getAttribute('expires_at') !== null && $this->expired($client->getAttribute('expires_at')))
            || ! in_array($requiredGrant->value, $this->stringList($client->getAttribute('allowed_grant_types')), true)) {
            throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'The OAuth client is unavailable.', 401);
        }
    }

    private function assertExactRedirect(AuthClientModel $client, string $redirectUri): void
    {
        if (! in_array($redirectUri, $this->stringList($client->getAttribute('redirect_uris')), true)) {
            throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'The OAuth redirect URI is not registered.', 400);
        }
    }

    private function assertClientSecret(AuthClientModel $client, ?string $clientSecret): void
    {
        if (! (bool) $client->getAttribute('is_confidential')) {
            return;
        }

        $hash = $client->getAttribute('client_secret_hash');
        if (! is_string($clientSecret) || $clientSecret === '' || ! is_string($hash) || ! $this->passwords->verify($clientSecret, $hash)) {
            throw new AuthFailure(AuthErrorCode::INVALID_CLIENT_SECRET, 'The OAuth client credentials are invalid.', 401);
        }
    }

    /** @param list<string> $requested @return list<string> */
    private function authorizeScopes(AuthClientModel $client, array $requested): array
    {
        $catalogue = $this->config->oauthScopes;
        $allowed = $this->stringList($client->getAttribute('allowed_scopes'));
        $scopes = [];
        foreach ($requested as $scope) {
            if (! is_string($scope)) {
                continue;
            }
            $scope = trim($scope);
            if ($scope === '' || ! in_array($scope, $catalogue, true) || ! in_array($scope, $allowed, true)) {
                throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'One or more OAuth scopes are not allowed.', 403);
            }
            $scopes[] = $scope;
        }
        $scopes = array_values(array_unique($scopes));
        if ($scopes === []) {
            throw new AuthFailure(AuthErrorCode::CLIENT_NOT_ALLOWED, 'At least one allowed OAuth scope is required.', 422);
        }
        return $scopes;
    }

    private function assertPkce(string $challenge, string $verifier): void
    {
        $derived = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        if (! hash_equals($challenge, $derived)) {
            throw $this->invalidCode();
        }
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        $result = [];
        foreach ($value as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $result[] = trim($entry);
            }
        }
        return array_values(array_unique($result));
    }

    private function expired(mixed $value): bool
    {
        return $value === null || $this->clock->now()->getTimestamp() >= $value->getTimestamp();
    }

    private function invalidCode(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::AUTHORIZATION_CODE_INVALID, 'The authorization code is invalid or expired.', 400);
    }
}
