<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model
 *
 * Manages customer data with support for both individual and business customers.
 * Includes credit limits, payment terms, and multi-address support.
 */
class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'tax_id',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'credit_limit',
        'payment_terms_days',
        'preferences',
        'metadata',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'preferences' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Tenant::class);
    }

    /**
     * Get all contacts for the customer.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    /**
     * Get all vehicles owned by the customer.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the primary contact.
     */
    public function primaryContact()
    {
        return $this->hasOne(CustomerContact::class)->where('is_primary', true);
    }

    /**
     * Get full name for individual customers.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'individual') {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return $this->company_name;
    }

    /**
     * Scope active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by customer type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
