<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class VehicleAuthorizationService
{
    public const VIEW = 'vehicle.view';
    public const CREATE = 'vehicle.create';
    public const UPDATE = 'vehicle.update';
    public const DELETE = 'vehicle.delete';
    public const MANAGE_DOCUMENTS = 'vehicle.documents.manage';
    public const DOWNLOAD_DOCUMENTS = 'vehicle.documents.download';
    public const MANAGE_ATTRIBUTES = 'vehicle.attributes.manage';
    public const CHANGE_STATUS = 'vehicle.status.change';

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Vehicle action requires permission: '.$permission);
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
