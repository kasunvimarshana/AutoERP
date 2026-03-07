<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * Minimal User model for Passport token validation.
 *
 * The product-service does not own user data; this model is used solely to
 * authenticate API requests via Passport tokens issued by the user-service.
 * User records are expected to be readable from a shared `users` table or
 * via a read-only replica; actual writes happen only in the user-service.
 */
class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'tenant_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check whether the user has a specific role.
     * Requires the user model to have a `roles` relationship or a `role` column.
     * Defaults to a no-op that returns false when roles aren't locally stored.
     */
    public function hasRole(string $role): bool
    {
        if (method_exists($this, 'roles')) {
            return $this->roles()->where('name', $role)->exists();
        }

        return false;
    }

    /**
     * Check whether the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (method_exists($this, 'permissions')) {
            return $this->permissions()->where('name', $permission)->exists();
        }

        return false;
    }
}
