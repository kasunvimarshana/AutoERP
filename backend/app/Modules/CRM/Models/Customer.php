<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\MasterData\Models\Currency;
use App\Modules\Organization\Models\Organization;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'customer_code',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'tax_id',
        'registration_number',
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
        'currency_id',
        'price_list_id',
        'credit_limit',
        'payment_terms_days',
        'discount_percentage',
        'category',
        'group',
        'status',
        'lead_source',
        'sales_rep',
        'first_contact_date',
        'last_contact_date',
        'notes',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'discount_percentage' => 'decimal:2',
        'first_contact_date' => 'date',
        'last_contact_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the organization that owns the customer.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the customer's currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the customer's price list.
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Get the contacts for the customer.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the primary contact for the customer.
     */
    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('is_primary', true);
    }

    /**
     * Scope to get only active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company') {
            return $this->company_name;
        }
        
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get customer's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        return $name ? "{$this->customer_code} - {$name}" : $this->customer_code;
    }

    /**
     * Check if customer has exceeded credit limit.
     */
    public function hasCreditLimitExceeded(float $additionalAmount = 0): bool
    {
        // This would typically check against actual outstanding invoices
        // For now, just a placeholder
        return false;
    }

    /**
     * Get customer's available credit.
     */
    public function getAvailableCreditAttribute(): float
    {
        // This would calculate based on credit_limit minus outstanding invoices
        return $this->credit_limit;
    }
}
