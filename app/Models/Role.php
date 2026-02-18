<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasUuid;

/**
 * Role Model
 *
 * Represents a user role with associated permissions.
 * Works with the native HasPermissions trait for RBAC.
 *
 * No external packages required - pure Laravel Eloquent.
 */
class Role extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', User::class),
            'user_roles',
            'role_id',
            'user_id'
        );
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function givePermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];

        if (! in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }

        return $this;
    }

    public function revokePermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];

        $this->permissions = array_values(array_filter(
            $permissions,
            fn ($p) => $p !== $permission
        ));

        $this->save();

        return $this;
    }

    /**
     * Scope to filter only active roles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
