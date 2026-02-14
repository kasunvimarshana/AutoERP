<?php

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Subscription Plan Model
 *
 * Defines available subscription tiers and pricing.
 */
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'features',
        'max_users',
        'max_branches',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get all subscriptions using this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Scope active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
