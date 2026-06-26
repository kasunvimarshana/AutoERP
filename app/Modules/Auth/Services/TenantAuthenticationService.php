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
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\Security\AccountLoginThrottle;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantAuthenticationDirectoryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Throwable;

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
        private AccountLoginThrottle $throttle,
        private TenantTokenService $tokens,
        private TenantAuthProfileBuilder $profiles,
        private LoginAttemptRecorder $attempts,
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
        $userId = null;

        if ($identifier === '' || $this->throttle->isBlocked(self::REALM, $identifier, $client->ipAddress)) {
            $this->credentials->verifyDummy($password);
            $this->attempts->recordTenantFailureBestEffort(
                $tenantId,
                null,
                $identifier,
                AuthErrorCode::RATE_LIMITED,
                $client,
            );
            throw $this->invalidCredentials();
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
            $this->attempts->recordTenantFailureBestEffort(
                $tenantId,
                $userId,
                $identifier,
                AuthErrorCode::INVALID_CREDENTIALS,
                $client,
            );
            throw $this->invalidCredentials();
        }

        try {
            $payload = $this->executionContext->runForTenant($tenantId, fn (): array => $this->database->transaction(
                function () use ($tenantId, $user, $requestedOrganizationUnitId, $identifier, $client): array {
                    $organizationUnitId = $this->users->resolveLoginOrganizationUnit(
                        $tenantId,
                        (int) $user['id'],
                        $requestedOrganizationUnitId,
                    );
                    if ($organizationUnitId === null) {
                        throw new AuthFailure(
                            AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                            $requestedOrganizationUnitId === null
                                ? 'A valid default organization unit is required.'
                                : 'The selected organization unit is unavailable.',
                            $requestedOrganizationUnitId === null ? 409 : 422,
                            ['stage' => 'organization_access'],
                        );
                    }

                    $provider = AuthProviderModel::query()
                        ->where('tenant_id', $tenantId)
                        ->where('provider_key', (string) config('module-auth.internal_provider_key', 'internal'))
                        ->where('status', ProviderStatus::ACTIVE->value)
                        ->lockForUpdate()
                        ->first();
                    if (! $provider instanceof AuthProviderModel) {
                        throw new AuthFailure(
                            AuthErrorCode::PROVIDER_NOT_FOUND,
                            'Authentication is temporarily unavailable.',
                            503,
                            ['stage' => 'authentication_provider'],
                        );
                    }

                    $identity = AuthIdentityModel::query()
                        ->where('tenant_id', $tenantId)
                        ->where('provider_id', $provider->getKey())
                        ->where('user_id', (int) $user['id'])
                        ->where('status', IdentityStatus::ACTIVE->value)
                        ->lockForUpdate()
                        ->first();
                    if (! $identity instanceof AuthIdentityModel) {
                        throw $this->invalidCredentials();
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

                    $tokens = $this->tokens->issueSessionTokens(
                        $tenantId,
                        (int) $session->getKey(),
                        (int) $user['id'],
                        null,
                        ['tenant'],
                        GrantType::TENANT_PASSWORD,
                    );
                    $profile = $this->profiles->build([
                        'tenant_id' => $tenantId,
                        'tenant_user_id' => (int) $user['id'],
                        'organization_unit_id' => $organizationUnitId,
                    ]);

                    $this->attempts->recordTenant(
                        $tenantId,
                        (int) $user['id'],
                        $identifier,
                        true,
                        null,
                        $client,
                    );

                    return array_merge([
                        'tokens' => $tokens,
                        'session' => [
                            'id' => (string) $session->getAttribute('public_id'),
                            'expires_at' => $session->getAttribute('expires_at')?->format(DATE_ATOM),
                        ],
                    ], $profile);
                },
                3,
            ));
        } catch (AuthFailure $exception) {
            $this->throttle->recordFailure(self::REALM, $identifier, $client->ipAddress);
            $this->attempts->recordTenantFailureBestEffort(
                $tenantId,
                $userId,
                $identifier,
                $exception->errorCode,
                $client,
            );
            throw $exception;
        } catch (Throwable $exception) {
            $this->attempts->recordTenantFailureBestEffort(
                $tenantId,
                $userId,
                $identifier,
                AuthErrorCode::INFRASTRUCTURE_FAILURE,
                $client,
            );
            throw $exception;
        }

        $this->throttle->clearSuccessful(self::REALM, $identifier, $client->ipAddress);

        return $payload;
    }

    private function invalidCredentials(): AuthFailure
    {
        return new AuthFailure(
            AuthErrorCode::INVALID_CREDENTIALS,
            'The credentials are invalid.',
            401,
            ['stage' => 'credentials'],
        );
    }
}
