<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Tenant Model
 * 
 * Represents a tenant in the multi-tenant SaaS application.
 * Each tenant is an isolated organization with its own data,
 * users, and configuration.
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'status',
        'settings',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Get the users for the tenant.
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if tenant is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is on trial
     *
     * @return bool
     */
    public function isOnTrial(): bool
    {
        if (!$this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant subscription is active
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isFuture() && $this->status === 'active';
    }

    /**
     * Activate the tenant
     *
     * @return bool
     */
    public function activate(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Suspend the tenant
     *
     * @return bool
     */
    public function suspend(): bool
    {
        $this->status = 'suspended';
        return $this->save();
    }

    /**
     * Cancel the tenant
     *
     * @return bool
     */
    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }
}
