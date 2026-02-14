<?php

namespace App\Modules\IAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Model
 * 
 * Represents system permissions for RBAC/ABAC.
 * Permissions are global and not tenant-specific.
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
        'group',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if permission belongs to a specific module
     */
    public function isForModule(string $module): bool
    {
        return $this->module === $module;
    }
}
