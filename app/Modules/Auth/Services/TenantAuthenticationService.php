<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Enums\IdentityStatus;
use Modules\Auth\Enums\ProviderStatus;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthIdentityModel;
use Modules\Auth\Models\AuthLoginAttemptModel;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\LoginThrottle;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantAuthenticationDirectoryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;

final readonly class TenantAuthenticationService
{
    private const REALM = 'tenant';

    public function __construct(
        private DatabaseManager $database,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private TenantAuthenticationDirectoryInterface $tenants,
        private TenantUserAuthenticationDirectoryInterface $users,
        private PasswordCredentialService $credentials,
        private LoginThrottle $throttle,
        private TokenService $tokens,
        private AuthSecurityConfig $config,
    ) {}

    /** @return array<string,mixed> */
    public function login(
        int $tenantId,
        string $identifier,
        string $password,
        ?int $requestedOrganizationUnitId,
        ClientContext $client,
    ): array {
        $identifier = mb_strtolower(trim($identifier));
        $failure = $this->invalidCredentials();
        $userId = null;

        if ($identifier === '' || $this->throttle->isBlocked(self::REALM, $identifier, $client->ipAddress)) {
            $this->credentials->verifyDummy($password);
            $this->recordAttempt($tenantId, null, $identifier, false, 'rate_limited', $client);
            throw $failure;
        }

        $tenant = $this->tenants->findActive($tenantId);
        $user = $tenant === null ? null : $this->users->findTenantForLogin($tenantId, $identifier);
        if ($user !== null) {
            $userId = (int) $user['id'];
        }

        $validUser = $user !== null
            && (string) ($user['status'] ?? '') === 'active'
            && (bool) ($user['credentials_ready'] ?? false);
        $passwordValid = $validUser
            ? $this->credentials->verifyTenantUser($tenantId, (int) $user['id'], $password)
            : false;

        if (! $validUser || ! $passwordValid) {
            if (! $validUser) {
                $this->credentials->verifyDummy($password);
            }
            $this->throttle->recordFailure(self::REALM, $identifier, $client->ipAddress);
            $this->recordAttempt($tenantId, $userId, $identifier, false, AuthErrorCode::INVALID_CREDENTIALS, $client);
            throw $failure;
        }

        $organizationUnitId = $this->resolveOrganizationUnit(
            $tenantId,
            (int) $user['id'],
            $requestedOrganizationUnitId,
        );

        try {
            $payload = $this->executionContext->runForTenant($tenantId, fn (): array => $this->database->transaction(
                function () use ($tenantId, $user, $organizationUnitId, $client): array {
                    $provider = AuthProviderModel::query()
                        ->where('provider_key', (string) config('module-auth.internal_provider_key', 'internal'))
                        ->where('status', ProviderStatus::ACTIVE->value)
                        ->lockForUpdate()
                        ->first();
                    if (! $provider instanceof AuthProviderModel) {
                        throw new AuthFailure(AuthErrorCode::PROVIDER_NOT_FOUND, 'Authentication is unavailable.', 503);
                    }

                    $identity = AuthIdentityModel::query()
                        ->where('provider_id', $provider->getKey())
                        ->where('user_id', (int) $user['id'])
                        ->where('status', IdentityStatus::ACTIVE->value)
                        ->lockForUpdate()
                        ->first();
                    if (! $identity instanceof AuthIdentityModel) {
                        throw new AuthFailure(AuthErrorCode::INVALID_CREDENTIALS, 'The credentials are invalid.', 401);
                    }

                    $now = $this->clock->now();
                    $session = AuthSessionModel::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'provider_id' => (int) $provider->getKey(),
                        'identity_id' => (int) $identity->getKey(),
                        'user_id' => (int) $user['id'],
                        'status' => SessionStatus::ACTIVE->value,
                        'ip_address' => $client->ipAddress,
                        'user_agent' => $client->userAgent,
                        'device_name' => $client->deviceName,
                        'authenticated_at' => $now,
                        'last_activity_at' => $now,
                        'expires_at' => $now->modify('+'.$this->config->tenantSessionTtlSeconds.' seconds'),
                        'row_version' => 1,
                    ]);

                    $identity->forceFill([
                        'last_authenticated_at' => $now,
                        'row_version' => (int) $identity->getAttribute('row_version') + 1,
                    ])->save();

                    return [
                        'tokens' => $this->tokens->issueTenantSessionTokens(
                            $tenantId,
                            (int) $session->getKey(),
                            (int) $user['id'],
                            null,
                            ['tenant'],
                            GrantType::TENANT_PASSWORD,
                        ),
                        'session' => [
                            'id' => (string) $session->getAttribute('public_id'),
                            'expires_at' => $session->getAttribute('expires_at')?->format(DATE_ATOM),
                        ],
                    ];
                },
                3,
            ));
        } catch (AuthFailure $exception) {
            $this->throttle->recordFailure(self::REALM, $identifier, $client->ipAddress);
            $this->recordAttempt($tenantId, $userId, $identifier, false, $exception->errorCode, $client);
            throw $exception;
        }

        $this->throttle->clearSuccessful(self::REALM, $identifier, $client->ipAddress);
        $this->recordAttempt($tenantId, (int) $user['id'], $identifier, true, null, $client);

        return $payload;
    }

    private function resolveOrganizationUnit(int $tenantId, int $userId, ?int $requested): int
    {
        if ($requested !== null) {
            if (! $this->users->canAccessOrganizationUnit($tenantId, $userId, $requested)) {
                throw new AuthFailure(
                    AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                    'The selected organization unit is unavailable.',
                    422,
                );
            }
            return $requested;
        }

        $defaults = $this->users->defaultOrganizationUnitIds($tenantId, $userId);
        if (count($defaults) !== 1 || ! $this->users->canAccessOrganizationUnit($tenantId, $userId, $defaults[0])) {
            throw new AuthFailure(
                AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                'A valid default organization unit is required.',
                409,
            );
        }

        return $defaults[0];
    }

    private function recordAttempt(
        int $tenantId,
        ?int $userId,
        string $identifier,
        bool $successful,
        ?string $failureCode,
        ClientContext $client,
    ): void {
        try {
            $this->executionContext->runForTenant($tenantId, fn () => AuthLoginAttemptModel::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'login_identifier_hash' => hash_hmac('sha256', $identifier, (string) config('app.key')),
                'was_successful' => $successful,
                'failure_code' => $failureCode,
                'ip_address' => $client->ipAddress,
                'user_agent' => $client->userAgent,
                'attempted_at' => $this->clock->now(),
            ]));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function invalidCredentials(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::INVALID_CREDENTIALS, 'The credentials are invalid.', 401);
    }
}
