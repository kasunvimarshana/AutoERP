<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ItemAuthorizationService
{
    public const VIEW = 'item.view';

    public const CREATE = 'item.create';

    public const UPDATE = 'item.update';

    public const ACTIVATE = 'item.activate';

    public const DEACTIVATE = 'item.deactivate';

    public const DELETE = 'item.delete';

    public const MANAGE_UNITS = 'item.units.manage';

    public const CHANGE_BASE_UOM = 'item.base_uom.change';

    public const MANAGE_VARIANTS = 'item.variants.manage';

    public const MANAGE_BUNDLES = 'item.bundles.manage';

    public const MANAGE_PRICES = 'item.prices.manage';

    public const MANAGE_CODES = 'item.codes.manage';

    public const MANAGE_USAGE_RULES = 'item.usage_rules.manage';

    public const MANAGE_CATEGORIES = 'item.categories.manage';

    public const MANAGE_BRANDS = 'item.brands.manage';

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View item master data and related read-only resources.',
            self::CREATE => 'Create item master records and initial related data.',
            self::UPDATE => 'Update item master fields.',
            self::ACTIVATE => 'Activate item master records.',
            self::DEACTIVATE => 'Deactivate item master records.',
            self::DELETE => 'Delete unused item master records.',
            self::MANAGE_UNITS => 'Create, update, and delete item unit assignments.',
            self::CHANGE_BASE_UOM => 'Preview and apply item base UOM changes.',
            self::MANAGE_VARIANTS => 'Create, update, and delete item variants.',
            self::MANAGE_BUNDLES => 'Create, update, and delete item bundle lines.',
            self::MANAGE_PRICES => 'Create, update, and delete item prices.',
            self::MANAGE_CODES => 'Create, update, and delete item codes.',
            self::MANAGE_USAGE_RULES => 'Create, update, and delete item usage rules.',
            self::MANAGE_CATEGORIES => 'Create, update, and delete item categories.',
            self::MANAGE_BRANDS => 'Create, update, and delete item brands.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Item action requires permission: '.$permission);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    public function assertAny(?int $userId, int $tenantId, array $permissions): void
    {
        if ($userId === null) {
            throw new AuthorizationException('This Item action requires one of: '.implode(', ', $permissions));
        }

        foreach ($permissions as $permission) {
            if ($this->can($userId, $tenantId, $permission)) {
                return;
            }
        }

        throw new AuthorizationException('This Item action requires one of: '.implode(', ', $permissions));
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        if (! array_key_exists($permission, self::descriptions())) {
            return false;
        }

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

        if (DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.tenant_id', $tenantId)
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.name', $permission)
            ->whereNull('permissions.deleted_at')
            ->exists()) {
            return true;
        }

        if ($roleIds->isEmpty()) {
            return false;
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
