<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant Model
 *
 * Represents a tenant in the multi-tenant system.
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'phone',
        'address',
        'logo',
        'settings',
        'is_active',
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
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Get the users for the tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if tenant subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        if (! $this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if tenant is in trial period
     */
    public function isInTrial(): bool
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isFuture();
    }
}
