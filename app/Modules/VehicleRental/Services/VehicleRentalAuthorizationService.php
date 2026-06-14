<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class VehicleRentalAuthorizationService
{
    public const MANAGE_LINKS = 'vehicle-rental.links.manage';

    public const APPROVE_USAGE = 'vehicle-rental.usage.approve';

    public const OVERRIDE_MILEAGE = 'vehicle-rental.usage.mileage-override';

    public const CLASSIFY_HOLIDAY = 'vehicle-rental.usage.classify-holiday';

    public const APPROVE_EXPENSES = 'vehicle-rental.expenses.approve';

    public const GENERATE_CHARGES = 'vehicle-rental.charges.generate';

    public const APPROVE_CHARGES = 'vehicle-rental.charges.approve';

    public const CREATE_FINANCIAL_DOCUMENTS = 'vehicle-rental.financial.create';

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This VehicleRental action requires permission: '.$permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        $roleIds = DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->pluck('role_id');
        if (DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)
            ->where('name', 'Super Admin')
            ->whereNull('deleted_at')
            ->exists()) {
            return true;
        }

        $direct = DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.tenant_id', $tenantId)
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.name', $permission)
            ->whereNull('permissions.deleted_at')
            ->exists();
        if ($direct) {
            return true;
        }

        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.tenant_id', $tenantId)
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('permissions.name', $permission)
            ->whereNull('permissions.deleted_at')
            ->exists();
    }
}
