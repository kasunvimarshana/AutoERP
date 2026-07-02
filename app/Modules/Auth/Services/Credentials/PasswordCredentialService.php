<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Credentials;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Enums\CredentialStatus;
use Modules\Auth\Enums\IdentityStatus;
use Modules\Auth\Enums\ProviderStatus;
use Modules\Auth\Models\AuthIdentityModel;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthPlatformOperatorPasswordCredentialModel;
use Modules\Auth\Models\AuthUserPasswordCredentialModel;
use Modules\Auth\Security\PasswordPolicy;
use Modules\Core\Contracts\ClockInterface;
use Modules\Auth\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Contracts\TenantUserCredentialProvisionerInterface;

final readonly class PasswordCredentialService implements PlatformOperatorCredentialProvisionerInterface, TenantUserCredentialProvisionerInterface
{
    private const INTERNAL_PROVIDER_NAME = 'Internal authentication';
    private const INTERNAL_PROVIDER_DRIVER = 'internal';

    public function __construct(
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
        private DatabaseManager $database,
        private TenantExecutionContextInterface $executionContext,
    ) {}

    public function passwordRequirements(): array
    {
        return PasswordPolicy::requirements();
    }

    public function setTenantUserPassword(int $tenantId, int $userId, string $plainPassword): void
    {
        PasswordPolicy::assert($plainPassword);
        $this->executionContext->runForTenant($tenantId, fn () => $this->database->transaction(function () use ($tenantId, $userId, $plainPassword): void {
            $this->upsertTenantUserPasswordCredential($tenantId, $userId, $plainPassword);
        }, 3));
    }

    public function provisionTenantUser(int $tenantId, int $userId, string $email, string $plainPassword): void
    {
        PasswordPolicy::assert($plainPassword);
        $email = mb_strtolower(trim($email));

        $this->executionContext->runForTenant($tenantId, fn () => $this->database->transaction(function () use (
            $tenantId,
            $userId,
            $email,
            $plainPassword,
        ): void {
            $provider = AuthProviderModel::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'provider_key' => (string) config('module-auth.internal_provider_key', self::INTERNAL_PROVIDER_DRIVER),
                ],
                [
                    'name' => self::INTERNAL_PROVIDER_NAME,
                    'driver' => self::INTERNAL_PROVIDER_DRIVER,
                    'status' => ProviderStatus::ACTIVE->value,
                    'row_version' => 1,
                ],
            );
            if ((string) $provider->getAttribute('status') !== ProviderStatus::ACTIVE->value) {
                throw new \RuntimeException('The internal authentication provider is inactive.');
            }

            $identity = AuthIdentityModel::query()
                ->where('provider_id', $provider->getKey())
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if (! $identity instanceof AuthIdentityModel) {
                AuthIdentityModel::query()->create([
                    'tenant_id' => $tenantId,
                    'provider_id' => (int) $provider->getKey(),
                    'user_id' => $userId,
                    'provider_user_key' => $email,
                    'status' => IdentityStatus::ACTIVE->value,
                    'primary_marker' => 'primary',
                    'verified_at' => $this->clock->now(),
                    'row_version' => 1,
                ]);
            } else {
                if ((string) $identity->getAttribute('status') !== IdentityStatus::ACTIVE->value) {
                    throw new \RuntimeException('The user authentication identity is inactive.');
                }
                $identity->forceFill([
                    'provider_user_key' => $email,
                    'verified_at' => $identity->getAttribute('verified_at') ?? $this->clock->now(),
                    'row_version' => (int) $identity->getAttribute('row_version') + 1,
                ])->save();
            }

            $this->upsertTenantUserPasswordCredential($tenantId, $userId, $plainPassword);
        }, 3));
    }

    public function verifyTenantUser(int $tenantId, int $userId, string $plainPassword): bool
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($userId, $plainPassword): bool {
            $credential = AuthUserPasswordCredentialModel::query()
                ->where('user_id', $userId)->where('status', CredentialStatus::ACTIVE->value)
                ->whereNull('revoked_at')->first();
            if (! $credential instanceof AuthUserPasswordCredentialModel) {
                return false;
            }
            $hash = (string) $credential->getAttribute('password_hash');
            if ($hash === '' || ! $this->hasher->verify($plainPassword, $hash)) {
                return false;
            }
            if ($this->hasher->needsRehash($hash)) {
                $this->database->transaction(function () use ($credential, $plainPassword, $hash): void {
                    $locked = AuthUserPasswordCredentialModel::query()
                        ->whereKey($credential->getKey())->lockForUpdate()->first();
                    if ($locked instanceof AuthUserPasswordCredentialModel
                        && hash_equals($hash, (string) $locked->getAttribute('password_hash'))) {
                        $locked->forceFill([
                            'password_hash' => $this->hasher->hash($plainPassword),
                            'changed_at' => $this->clock->now(),
                            'row_version' => (int) $locked->getAttribute('row_version') + 1,
                        ])->save();
                    }
                }, 3);
            }
            return true;
        });
    }

    public function verifyDummy(string $plainPassword): void
    {
        static $dummyHash = null;
        $dummyHash ??= $this->hasher->hash('Dummy-Authentication-Password-Only-For-Timing-2026!');
        $this->hasher->verify($plainPassword, $dummyHash);
    }

    public function revokeTenantUser(int $tenantId, int $userId): void
    {
        $this->executionContext->runForTenant($tenantId, fn () => AuthUserPasswordCredentialModel::query()
            ->where('user_id', $userId)->where('status', CredentialStatus::ACTIVE->value)
            ->increment('row_version', 1, [
                'status' => CredentialStatus::REVOKED->value,
                'revoked_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]));
    }

    public function provision(int $platformOperatorId, string $plainPassword): void
    {
        PasswordPolicy::assert($plainPassword);
        $this->executionContext->runAsControlPlane(fn () => $this->database->transaction(function () use ($platformOperatorId, $plainPassword): void {
            $credential = AuthPlatformOperatorPasswordCredentialModel::query()
                ->where('platform_operator_id', $platformOperatorId)->lockForUpdate()->first();
            $credential ??= new AuthPlatformOperatorPasswordCredentialModel();
            $credential->forceFill([
                'platform_operator_id' => $platformOperatorId,
                'password_hash' => $this->hasher->hash($plainPassword),
                'status' => CredentialStatus::ACTIVE->value,
                'changed_at' => $this->clock->now(),
                'revoked_at' => null,
                'row_version' => $credential->exists
                    ? (int) $credential->getAttribute('row_version') + 1
                    : 1,
            ])->save();
        }, 3));
    }

    public function verifyPlatformOperator(int $operatorId, string $plainPassword): bool
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $plainPassword): bool {
            $credential = AuthPlatformOperatorPasswordCredentialModel::query()
                ->where('platform_operator_id', $operatorId)->where('status', CredentialStatus::ACTIVE->value)
                ->whereNull('revoked_at')->first();
            if (! $credential instanceof AuthPlatformOperatorPasswordCredentialModel) {
                return false;
            }
            $hash = (string) $credential->getAttribute('password_hash');
            if ($hash === '' || ! $this->hasher->verify($plainPassword, $hash)) {
                return false;
            }
            if ($this->hasher->needsRehash($hash)) {
                $this->database->transaction(function () use ($credential, $plainPassword, $hash): void {
                    $locked = AuthPlatformOperatorPasswordCredentialModel::query()
                        ->whereKey($credential->getKey())->lockForUpdate()->first();
                    if ($locked instanceof AuthPlatformOperatorPasswordCredentialModel
                        && hash_equals($hash, (string) $locked->getAttribute('password_hash'))) {
                        $locked->forceFill([
                            'password_hash' => $this->hasher->hash($plainPassword),
                            'changed_at' => $this->clock->now(),
                            'row_version' => (int) $locked->getAttribute('row_version') + 1,
                        ])->save();
                    }
                }, 3);
            }
            return true;
        });
    }

    private function upsertTenantUserPasswordCredential(int $tenantId, int $userId, string $plainPassword): void
    {
        $credential = AuthUserPasswordCredentialModel::query()
            ->where('user_id', $userId)->lockForUpdate()->first();
        $credential ??= new AuthUserPasswordCredentialModel();
        $credential->forceFill([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'password_hash' => $this->hasher->hash($plainPassword),
            'status' => CredentialStatus::ACTIVE->value,
            'changed_at' => $this->clock->now(),
            'revoked_at' => null,
            'row_version' => $credential->exists
                ? (int) $credential->getAttribute('row_version') + 1
                : 1,
        ])->save();
    }
}
