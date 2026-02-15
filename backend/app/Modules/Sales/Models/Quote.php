<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\CRM\Models\Customer;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Modules\MasterData\Models\Currency;

class Quote extends Model
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
        'quote_number',
        'customer_id',
        'customer_contact_name',
        'customer_email',
        'customer_phone',
        'status',
        'quote_date',
        'valid_until',
        'converted_at',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'adjustment_amount',
        'total_amount',
        'billing_address',
        'shipping_address',
        'notes',
        'internal_notes',
        'terms_and_conditions',
        'tags',
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
        'quote_date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the quote items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the sales orders created from this quote.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if quote is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    /**
     * Check if quote is expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until < now();
    }

    /**
     * Check if quote is converted.
     */
    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }
}
