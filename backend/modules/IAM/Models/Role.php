<?php

namespace Modules\IAM\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'tenant_id',
        'parent_id',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $permissions = $this->permissions;

        if ($this->parent) {
            $permissions = $permissions->merge($this->parent->getAllPermissions());
        }

        return $permissions->unique('id');
    }
}
