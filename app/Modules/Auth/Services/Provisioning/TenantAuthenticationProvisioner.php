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

    public function provisionProvider(int $tenantId): array
    {
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

        return ['provider_id' => (int) $provider->getKey()];
    }

    public function issueInitialAdministratorInvitation(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array {
        return $this->invitations->issueInitialAdministrator(
            $tenantId,
            $organizationUnitId,
            $roleId,
            $email,
        );
    }

    public function providerIsReady(int $tenantId, bool $lockForUpdate = false): bool
    {
        return AuthProviderModel::query()
            ->where('tenant_id', $tenantId)
            ->where('provider_key', self::INTERNAL_PROVIDER_KEY)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();
    }

    public function acceptedInitialAdministratorUserId(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): ?int {
        return $this->invitations->acceptedInitialAdministratorUserId(
            $tenantId,
            $invitationId,
            $lockForUpdate,
        );
    }

    public function hasPendingInitialAdministratorInvitation(int $tenantId, ?int $invitationId = null): bool
    {
        return $this->invitations->hasPendingInitialAdministratorInvitation($tenantId, $invitationId);
    }

    public function initialAdministratorInvitationStatus(int $tenantId, ?int $invitationId = null): ?array
    {
        return $this->invitations->initialAdministratorStatus($tenantId, $invitationId);
    }

    public function resendInitialAdministratorInvitation(int $tenantId, int $invitationId, int $expectedVersion): array
    {
        return $this->invitations->resendInitialAdministrator($tenantId, $invitationId, $expectedVersion);
    }

    public function revokeInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        string $reason,
    ): void {
        $this->invitations->revokeInitialAdministrator($tenantId, $invitationId, $expectedVersion, $reason);
    }
    public function replaceInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        int $organizationUnitId,
        int $roleId,
        string $email,
        string $reason,
    ): array {
        return $this->invitations->replaceInitialAdministrator(
            $tenantId,
            $invitationId,
            $expectedVersion,
            $organizationUnitId,
            $roleId,
            $email,
            $reason,
        );
    }

}
