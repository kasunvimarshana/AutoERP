<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'description',
    ];

    /**
     * Scope permissions by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
