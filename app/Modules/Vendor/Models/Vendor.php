<?php

namespace App\Modules\Vendor\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendor Model
 *
 * Represents suppliers and vendors for procurement
 */
class Vendor extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'email',
        'phone',
        'mobile',
        'website',
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
     * Get the tenant that owns the vendor
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Check if vendor is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
