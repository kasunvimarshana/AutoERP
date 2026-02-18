<?php

namespace App\Modules\IAM\Models;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role Model
 *
 * Represents a role in the RBAC system.
 */
class Role extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\IAM\RoleFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system_role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    /**
     * Get the tenant that owns the role
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The permissions that belong to the role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * The users that have this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Grant permission to role
     */
    public function grantPermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }
}
