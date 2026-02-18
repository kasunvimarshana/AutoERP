<?php

declare(strict_types=1);

namespace Modules\IAM\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\LogsActivity;
use Modules\Core\Traits\Tenantable;
use Modules\IAM\Database\Factories\RoleFactory;

/**
 * Role Model
 *
 * Represents a role in the RBAC system that can be assigned to users.
 * Supports hierarchical roles with parent-child relationships.
 * Uses native Laravel Eloquent - no external packages.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $level
 * @property array|null $permissions
 * @property bool $is_system
 * @property bool $is_active
 */
class Role extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Tenantable;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'level',
        'permissions',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent role.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    /**
     * Get child roles.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    /**
     * Get all permissions associated with this role.
     */
    public function rolePermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Get all users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\Models\User'),
            'user_roles',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get all groups that have this role.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'group_role',
            'role_id',
            'group_id'
        )->withTimestamps();
    }

    /**
     * Scope to filter only active roles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter system roles.
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter custom (non-system) roles.
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to filter top-level roles (no parent).
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to filter roles by level.
     */
    public function scopeLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Get all permissions for this role including inherited from parent.
     */
    public function getAllPermissions(): array
    {
        $permissions = $this->permissions ?? [];

        // Add permissions from Permission entities
        $rolePermissions = $this->rolePermissions()->active()->pluck('slug')->toArray();
        $permissions = array_merge($permissions, $rolePermissions);

        // Inherit permissions from parent role
        if ($this->parent) {
            $parentPermissions = $this->parent->getAllPermissions();
            $permissions = array_merge($permissions, $parentPermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getAllPermissions());
    }

    /**
     * Assign a permission to this role.
     */
    public function assignPermission(Permission $permission): void
    {
        $this->rolePermissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Remove a permission from this role.
     */
    public function removePermission(Permission $permission): void
    {
        $this->rolePermissions()->detach($permission->id);
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->rolePermissions()->sync($permissionIds);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set level based on parent
        static::saving(function ($role) {
            if ($role->parent_id) {
                $parent = Role::find($role->parent_id);
                $role->level = $parent ? $parent->level + 1 : 0;
            } else {
                $role->level = 0;
            }
        });

        // Prevent deletion of system roles
        static::deleting(function ($role) {
            if ($role->is_system) {
                throw new \RuntimeException('Cannot delete system role');
            }
        });
    }
}
