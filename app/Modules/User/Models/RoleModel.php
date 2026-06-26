<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class RoleModel extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'tenant_id', 'row_version', 'name', 'active_name_key', 'guard_name',
        'system_key', 'is_system', 'description', 'created_by_user_id',
        'updated_by_user_id', 'deleted_by_user_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $role): void {
            if ($role->exists && ($role->isDirty('tenant_id') || $role->isDirty('system_key') || $role->isDirty('is_system'))) {
                throw new LogicException('Role ownership and system identity are immutable.');
            }
            $name = trim((string) $role->getAttribute('name'));
            $role->setAttribute('name', $name);
            $role->setAttribute('active_name_key', $role->getAttribute('deleted_at') === null ? mb_strtolower($name) : null);
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['is_system' => 'boolean', 'row_version' => 'integer']);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermissionModel::class, 'role_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'role_id');
    }
}
