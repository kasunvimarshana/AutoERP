<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Contracts\TenantUserRegistrationInterface;
use Modules\User\Models\RoleModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserOrganizationUnitModel;
use Modules\User\Models\UserRoleModel;
use RuntimeException;

final class TenantUserRegistrationService implements TenantUserRegistrationInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly OrganizationUnitDirectoryInterface $organizationUnits,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    public function prepareFromInvitation(
        int $tenantId,
        ?int $targetUserId,
        ?int $organizationUnitId,
        ?int $roleId,
        string $firstName,
        ?string $lastName,
        string $email,
    ): int {
        return DB::transaction(function () use (
            $tenantId,
            $targetUserId,
            $organizationUnitId,
            $roleId,
            $firstName,
            $lastName,
            $email,
        ): int {
            $this->tenantLock->lock($tenantId);
            $email = strtolower(trim($email));
            if ($email === '') {
                throw new RuntimeException('Invitation email is required.');
            }

            $user = $targetUserId === null ? null : UserModel::query()
                ->where('tenant_id', $tenantId)->whereKey($targetUserId)
                ->lockForUpdate()->first();
            if ($targetUserId !== null && ! $user instanceof UserModel) {
                throw new RuntimeException('The invited user account no longer exists.');
            }

            if (! $user instanceof UserModel) {
                if (UserModel::withTrashed()->where('tenant_id', $tenantId)->where('email', $email)->exists()) {
                    throw new RuntimeException('An account already exists for this email address.');
                }
                $user = UserModel::query()->create([
                    'tenant_id' => $tenantId,
                    'row_version' => 1,
                    'first_name' => trim($firstName),
                    'last_name' => $this->nullable($lastName),
                    'email' => $email,
                    'status' => UserStatus::INVITED,
                    'invited_at' => $this->clock->now(),
                ]);
            } else {
                if ((string) $user->getAttribute('email') !== $email) {
                    throw new RuntimeException('Invitation email does not match the invited user account.');
                }
                if ((string) $user->getAttribute('status') !== UserStatus::INVITED
                    || $user->getAttribute('credentials_ready_at') !== null) {
                    throw new RuntimeException('The invited user account is no longer awaiting credential setup.');
                }
                $user->forceFill([
                    'first_name' => trim($firstName) !== '' ? trim($firstName) : $user->getAttribute('first_name'),
                    'last_name' => $lastName !== null ? $this->nullable($lastName) : $user->getAttribute('last_name'),
                    'row_version' => (int) $user->getAttribute('row_version') + 1,
                ])->save();
            }

            if ($roleId !== null) {
                $role = RoleModel::query()->where('tenant_id', $tenantId)->whereKey($roleId)
                    ->where('is_system', true)->lockForUpdate()->first();
                if (! $role instanceof RoleModel) {
                    throw new RuntimeException('The invitation role is unavailable.');
                }
                UserRoleModel::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                    'role_id' => $roleId,
                ], ['row_version' => 1]);
            }

            if ($organizationUnitId !== null) {
                if (! $this->organizationUnits->isActive($tenantId, $organizationUnitId, true)) {
                    throw new RuntimeException('The invitation organization unit is unavailable.');
                }
                UserOrganizationUnitModel::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                    'organization_unit_id' => $organizationUnitId,
                ], [
                    'status' => UserOrganizationUnitStatus::ACTIVE,
                    'is_default' => true,
                    'default_marker' => UserOrganizationUnitStatus::DEFAULT_MARKER,
                    'row_version' => 1,
                ]);
            }

            return (int) $user->getKey();
        }, 3);
    }

    public function activateAfterCredentialSetup(int $tenantId, int $userId): array
    {
        return DB::transaction(function () use ($tenantId, $userId): array {
            $this->tenantLock->lock($tenantId);
            $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)
                ->lockForUpdate()->first();
            if (! $user instanceof UserModel || (string) $user->getAttribute('status') !== UserStatus::INVITED) {
                throw new RuntimeException('The invited user cannot be activated.');
            }
            $hasDefaultOrganizationUnit = UserOrganizationUnitModel::query()
                ->where('tenant_id', $tenantId)->where('user_id', $userId)
                ->where('status', UserOrganizationUnitStatus::ACTIVE)->where('is_default', true)
                ->select('id')->lockForUpdate()->first() !== null;
            if (! $hasDefaultOrganizationUnit) {
                throw new RuntimeException('An active default organization unit is required before activation.');
            }
            $now = $this->clock->now();
            $user->forceFill([
                'status' => UserStatus::ACTIVE,
                'credentials_ready_at' => $now,
                'email_verified_at' => $user->getAttribute('email_verified_at') ?? $now,
                'activated_at' => $user->getAttribute('activated_at') ?? $now,
                'row_version' => (int) $user->getAttribute('row_version') + 1,
            ])->save();

            return $user->fresh()?->attributesToArray() ?? $user->attributesToArray();
        }, 3);
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

}
