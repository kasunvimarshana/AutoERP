<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * User Model - Multi-tenant aware user.
 * Supports multi-guard authentication and RBAC.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'organization_id',
        'branch_id',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'metadata' => 'array',
    ];

    /**
     * Define RBAC relationship.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has specific permission.
     * Enforces ABAC/RBAC logic.
     */
    public function hasPermission(string $permission, array $context = []): bool
    {
        // 1. Check RBAC (Role-Based Access Control)
        $hasRolePermission = $this->role->permissions()->where('slug', $permission)->exists();

        if (!$hasRolePermission) {
            return false;
        }

        // 2. Check ABAC (Attribute-Based Access Control)
        // e.g., "Only managers can approve orders over $10k"
        if (isset($context['amount']) && $permission === 'order.approve') {
            return $this->role->slug === 'manager' && $context['amount'] < 10000;
        }

        return true;
    }
}
