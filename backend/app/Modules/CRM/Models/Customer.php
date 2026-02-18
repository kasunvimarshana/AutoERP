<?php

namespace App\Modules\CRM\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CRM\CustomerFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'customer_type',
        'name',
        'email',
        'phone',
        'mobile',
        'company_name',
        'tax_number',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'credit_limit',
        'payment_terms_days',
        'discount_percentage',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    /**
     * Get the tenant that owns the customer
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope query to active customers only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by customer type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }
}
