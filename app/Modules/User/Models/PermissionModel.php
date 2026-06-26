<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class PermissionModel extends TenantOwnedModel
{
    protected $table = 'permissions';

    protected $fillable = [
        'tenant_id', 'row_version', 'name', 'guard_name', 'module', 'description', 'is_active',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $permission): void {
            if ($permission->isDirty(['tenant_id', 'name', 'guard_name', 'module'])) {
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
