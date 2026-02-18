<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

/**
 * Customer Model for AutoERP
 *
 * Represents a customer entity with comprehensive CRM capabilities
 * including multi-currency support, credit management, and tiered pricing.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_code
 * @property string $customer_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $fax
 * @property string|null $website
 * @property string|null $tax_id
 * @property string $customer_tier
 * @property string $payment_terms
 * @property int $payment_term_days
 * @property float $credit_limit
 * @property float $outstanding_balance
 * @property string $preferred_currency
 * @property string|null $billing_address_line1
 * @property string|null $billing_address_line2
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_country
 * @property string|null $billing_postal_code
 * @property string|null $shipping_address_line1
 * @property string|null $shipping_address_line2
 * @property string|null $shipping_city
 * @property string|null $shipping_state
 * @property string|null $shipping_country
 * @property string|null $shipping_postal_code
 * @property bool $is_active
 * @property string|null $notes
 * @property array|null $custom_fields
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Customer extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'customer_name',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'tax_id',
        'customer_tier',
        'payment_terms',
        'payment_term_days',
        'credit_limit',
        'outstanding_balance',
        'preferred_currency',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'is_active',
        'notes',
        'custom_fields',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'payment_term_days' => 'integer',
        'is_active' => 'boolean',
        'custom_fields' => 'array',
    ];

    protected $attributes = [
        'customer_tier' => 'standard',
        'payment_terms' => 'net_30',
        'payment_term_days' => 30,
        'credit_limit' => 0,
        'outstanding_balance' => 0,
        'preferred_currency' => 'USD',
        'is_active' => true,
    ];

    /**
     * Get all sales orders for this customer.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    /**
     * Get all quotations for this customer.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'customer_id');
    }

    /**
     * Check if customer has available credit.
     */
    public function hasAvailableCredit(float $requestedAmount): bool
    {
        $availableCredit = $this->credit_limit - $this->outstanding_balance;

        return $availableCredit >= $requestedAmount;
    }

    /**
     * Get customer's available credit.
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    /**
     * Get formatted billing address.
     */
    public function getFormattedBillingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_address_line1,
            $this->billing_address_line2,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postal_code,
            $this->billing_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get formatted shipping address.
     */
    public function getFormattedShippingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->shipping_address_line1,
            $this->shipping_address_line2,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_postal_code,
            $this->shipping_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if customer is in good standing (not exceeding credit limit).
     */
    public function isInGoodStanding(): bool
    {
        return $this->is_active && $this->outstanding_balance <= $this->credit_limit;
    }

    /**
     * Scope query to active customers only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to customers by tier.
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('customer_tier', $tier);
    }

    /**
     * Scope query to customers in good standing.
     */
    public function scopeInGoodStanding($query)
    {
        return $query->where('is_active', true)
            ->whereRaw('outstanding_balance <= credit_limit');
    }
}
