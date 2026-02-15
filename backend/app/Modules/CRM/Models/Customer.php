<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Modules\MasterData\Models\Currency;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'organization_id',
        'branch_id',
        'type',
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_number',
        'company_name',
        'industry',
        'employee_count',
        'established_date',
        'credit_limit',
        'payment_terms_days',
        'currency_id',
        'payment_method',
        'is_active',
        'is_verified',
        'status',
        'priority',
        'customer_group',
        'source',
        'assigned_to',
        'notes',
        'tags',
        'custom_fields',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'employee_count' => 'integer',
        'established_date' => 'date',
        'tags' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the customer.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the branch that owns the customer.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the currency for the customer.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user assigned to this customer.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the addresses for the customer.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get the billing addresses for the customer.
     */
    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->whereIn('type', ['billing', 'both']);
    }

    /**
     * Get the shipping addresses for the customer.
     */
    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->whereIn('type', ['shipping', 'both']);
    }

    /**
     * Get the primary address for the customer.
     */
    public function primaryAddress(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)
            ->where('is_primary', true);
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
    public function primaryContact(): HasMany
    {
        return $this->hasMany(Contact::class)
            ->where('is_primary', true);
    }

    /**
     * Get the notes for the customer.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    /**
     * Get the user who created the customer.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the customer.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if customer is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Check if customer is verified.
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if customer is business type.
     */
    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }

    /**
     * Check if customer is individual type.
     */
    public function isIndividual(): bool
    {
        return $this->type === 'individual';
    }

    /**
     * Get the customer's available credit.
     */
    public function getAvailableCredit(): float
    {
        // This would be calculated by subtracting outstanding invoices
        // For now, returning credit limit
        return (float) $this->credit_limit;
    }

    /**
     * Check if customer has exceeded credit limit.
     */
    public function hasCreditLimitExceeded(float $amount = 0): bool
    {
        return ($this->getAvailableCredit() - $amount) < 0;
    }
}
