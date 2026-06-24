<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Provisioning;

use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Services\Registration\RegistrationInvitationService;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;

final class TenantAuthenticationProvisioner implements TenantAuthenticationProvisionerInterface
{
    private const INTERNAL_PROVIDER_KEY = 'internal';

    public function __construct(
        private readonly RegistrationInvitationService $invitations,
    ) {}

    public function provisionInitialAdministrator(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array {
        $provider = AuthProviderModel::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'provider_key' => self::INTERNAL_PROVIDER_KEY,
            ],
            [
                'organization_unit_id' => null,
                'name' => 'Internal authentication',
                'guard_name' => 'web',
                'provider_name' => 'users',
                'driver' => self::INTERNAL_PROVIDER_KEY,
                'status' => 'active',
                'is_sso' => false,
                'config' => null,
                'metadata' => ['system_defined' => true],
                'row_version' => 1,
            ],
        );

        return [
            'provider_id' => (int) $provider->getKey(),
            ...$this->invitations->issueInitialAdministrator(
                $tenantId,
                $organizationUnitId,
                $roleId,
                $email,
            ),
        ];
    }

    public function isReady(int $tenantId): bool
    {
        return AuthProviderModel::query()
            ->where('tenant_id', $tenantId)
            ->where('provider_key', self::INTERNAL_PROVIDER_KEY)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists()
            && $this->invitations->hasUsableInitialAdministratorInvitation($tenantId);
    }
}
