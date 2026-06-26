<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\ProviderStatus;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Services\Registration\RegistrationInvitationService;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;

final readonly class TenantAuthenticationProvisioner implements TenantAuthenticationProvisionerInterface
{
    private const INTERNAL_PROVIDER_NAME = 'Internal authentication';
    private const INTERNAL_PROVIDER_DRIVER = 'internal';

    public function __construct(private RegistrationInvitationService $invitations) {}

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

    public function hasPendingInitialAdministratorInvitation(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): bool {
        return $this->invitations->hasPendingInitialAdministratorInvitation(
            $tenantId,
            $invitationId,
            $lockForUpdate,
        );
    }

    public function initialAdministratorInvitationStatus(
        int $tenantId,
        ?int $invitationId = null,
        bool $lockForUpdate = false,
    ): ?array {
        return $this->invitations->initialAdministratorStatus($tenantId, $invitationId, $lockForUpdate);
    }

    public function resendInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
    ): array {
        return $this->invitations->resendInitialAdministrator($tenantId, $invitationId, $expectedVersion);
    }

    public function revokeInitialAdministratorInvitation(
        int $tenantId,
        int $invitationId,
        int $expectedVersion,
        string $reason,
    ): void {
        $this->invitations->revokeInitialAdministrator(
            $tenantId,
            $invitationId,
            $expectedVersion,
            $reason,
        );
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
