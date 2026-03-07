<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_active',
        'sso_provider',
        'sso_id',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'metadata'          => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
                    ->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // RBAC helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether the user has a given role (by name).
     * Accepts a single role string or a pipe-separated list.
     */
    public function hasRole(string $role): bool
    {
        $roleNames = explode('|', $role);

        return $this->roles
            ->whereIn('name', $roleNames)
            ->isNotEmpty();
    }

    /**
     * Check whether the user has a given permission directly OR via a role.
     * Accepts a single permission string or a pipe-separated list.
     */
    public function hasPermission(string $permission): bool
    {
        $permNames = explode('|', $permission);

        // Direct permission
        if ($this->permissions->whereIn('name', $permNames)->isNotEmpty()) {
            return true;
        }

        // Via role
        foreach ($this->roles as $role) {
            if ($role->permissions->whereIn('name', $permNames)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the user has ALL of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // ABAC – attribute-based checks
    // -------------------------------------------------------------------------

    /**
     * Verify that the user belongs to the given tenant.
     */
    public function belongsToTenant(int|string $tenantId): bool
    {
        return (string) $this->tenant_id === (string) $tenantId;
    }

    /**
     * True when the user is active and is the owner of the resource tenant.
     */
    public function canManageTenant(int|string $tenantId): bool
    {
        return $this->is_active
            && $this->belongsToTenant($tenantId)
            && ($this->hasRole('admin') || $this->hasRole('super-admin'));
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int|string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
