<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant Model
 * 
 * Represents a tenant in the multi-tenant SaaS architecture.
 * Each tenant has isolated data across the entire application.
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'email',
        'phone',
        'address',
        'logo',
        'timezone',
        'currency',
        'locale',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get organizations belonging to this tenant
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(\App\Modules\Organization\Models\Organization::class);
    }

    /**
     * Get users belonging to this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Get roles belonging to this tenant
     */
    public function roles(): HasMany
    {
        return $this->hasMany(\App\Modules\IAM\Models\Role::class);
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if tenant subscription is valid
     */
    public function hasValidSubscription(): bool
    {
        if (!$this->subscription_ends_at) {
            return true; // Lifetime subscription
        }

        return $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if tenant is in trial period
     */
    public function isInTrial(): bool
    {
        if (!$this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isFuture();
    }
}
