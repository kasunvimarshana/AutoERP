<?php

namespace App\Modules\Customer\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model
 *
 * Represents customers/clients in the system
 */
class Customer extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'email',
        'phone',
        'mobile',
        'customer_type',
        'tax_number',
        'contact_person',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'payment_terms',
        'credit_limit',
        'is_active',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the customer
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Check if customer is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
