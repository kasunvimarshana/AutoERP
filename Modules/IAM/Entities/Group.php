<?php

declare(strict_types=1);

namespace Modules\IAM\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\LogsActivity;
use Modules\Core\Traits\Tenantable;

/**
 * Group Model
 *
 * Represents a group/team of users with shared roles and permissions.
 * Supports hierarchical structure with parent-child relationships.
 * Uses native Laravel Eloquent - no external packages.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property bool $is_active
 */
class Group extends Model
{
    use LogsActivity, SoftDeletes, Tenantable;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent group.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    /**
     * Get child groups.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    /**
     * Get all users in this group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\Models\User'),
            'group_user',
            'group_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get all roles assigned to this group.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.models.role', 'App\Models\Role'),
            'group_role',
            'group_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Scope to filter only active groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root groups (no parent).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get all permissions from assigned roles.
     */
    public function getPermissions(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions ?? []);
        }

        return array_unique($permissions);
    }

    /**
     * Check if group has a specific permission through its roles.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Add a user to this group.
     */
    public function addUser($user): self
    {
        if (! $this->users->contains($user)) {
            $this->users()->attach($user);
        }

        return $this;
    }

    /**
     * Remove a user from this group.
     */
    public function removeUser($user): self
    {
        $this->users()->detach($user);

        return $this;
    }

    /**
     * Assign a role to this group.
     */
    public function assignRole($role): self
    {
        if (! $this->roles->contains($role)) {
            $this->roles()->attach($role);
        }

        return $this;
    }

    /**
     * Remove a role from this group.
     */
    public function removeRole($role): self
    {
        $this->roles()->detach($role);

        return $this;
    }
}
