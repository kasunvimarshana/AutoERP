<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\ProviderStatus;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\User\Contracts\TenantUserCredentialProvisionerInterface;
use Modules\User\Contracts\TenantUserRegistrationInterface;

final readonly class TenantAuthenticationProvisioner implements TenantAuthenticationProvisionerInterface
{
    private const INTERNAL_PROVIDER_NAME = 'Internal authentication';
    private const INTERNAL_PROVIDER_DRIVER = 'internal';

    public function __construct(
        private TenantUserRegistrationInterface $users,
        private TenantUserCredentialProvisionerInterface $credentials,
    ) {}

    public function provisionProvider(int $tenantId): array
    {
        if ($tenantId < 1) {
            throw new \InvalidArgumentException('A valid tenant identifier is required.');
        }

        return DB::transaction(function () use ($tenantId): array {
            $providerKey = (string) config('module-auth.internal_provider_key', self::INTERNAL_PROVIDER_DRIVER);
            $provider = AuthProviderModel::query()
                ->where('tenant_id', $tenantId)
                ->where('provider_key', $providerKey)
                ->lockForUpdate()
                ->first();

            $attributes = [
                'tenant_id' => $tenantId,
                'provider_key' => $providerKey,
                'name' => self::INTERNAL_PROVIDER_NAME,
                'driver' => self::INTERNAL_PROVIDER_DRIVER,
                'status' => ProviderStatus::ACTIVE->value,
            ];

            if ($provider instanceof AuthProviderModel) {
                $provider->forceFill([
                    ...$attributes,
                    'row_version' => (int) $provider->getAttribute('row_version') + 1,
                ])->save();
            } else {
                $provider = AuthProviderModel::query()->create([
                    ...$attributes,
                    'row_version' => 1,
                ]);
            }

            return ['provider_id' => (int) $provider->getKey()];
        }, 3);
    }

    public function provisionInitialAdministratorAccount(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $firstName,
        ?string $lastName,
        string $email,
        string $password,
    ): array {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $roleId,
            $firstName,
            $lastName,
            $email,
            $password,
        ): array {
            $userId = $this->users->prepareProvisionedAccount(
                $tenantId,
                $organizationUnitId,
                $roleId,
                $firstName,
                $lastName,
                $email,
            );
            $this->credentials->provisionTenantUser($tenantId, $userId, $email, $password);
            $user = $this->users->activateAfterCredentialSetup($tenantId, $userId);

            return [
                'user_id' => $userId,
                'email' => mb_strtolower(trim($email)),
                'status' => (string) ($user['status'] ?? 'active'),
            ];
        }, 3);
    }

    public function providerIsReady(int $tenantId, bool $lockForUpdate = false): bool
    {
        $query = AuthProviderModel::query()
            ->where('tenant_id', $tenantId)
            ->where('provider_key', (string) config('module-auth.internal_provider_key', self::INTERNAL_PROVIDER_DRIVER))
            ->where('driver', self::INTERNAL_PROVIDER_DRIVER)
            ->where('status', ProviderStatus::ACTIVE->value);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

}
