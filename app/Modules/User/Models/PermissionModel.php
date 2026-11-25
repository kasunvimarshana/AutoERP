<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class PermissionModel extends TenantOwnedModel
{
    protected $table = 'permissions';

    private static bool $allowCatalogueSync = false;

    protected $fillable = [
        'tenant_id', 'row_version', 'name', 'guard_name', 'module', 'description', 'is_active',
    ];

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function allowingCatalogueSync(callable $callback): mixed
    {
        $previous = self::$allowCatalogueSync;
        self::$allowCatalogueSync = true;
        try {
            return $callback();
        } finally {
            self::$allowCatalogueSync = $previous;
        }
    }

    protected static function booted(): void
    {
        self::updating(function (self $permission): void {
            $immutable = self::$allowCatalogueSync
                ? ['tenant_id', 'name', 'guard_name']
                : ['tenant_id', 'name', 'guard_name', 'module'];

            if ($permission->isDirty($immutable)) {
                throw new LogicException('Permission catalogue identity is immutable; synchronize the owning module catalogue instead.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['is_active' => 'boolean', 'row_version' => 'integer']);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(RolePermissionModel::class, 'permission_id');
    }
}
