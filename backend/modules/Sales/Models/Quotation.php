<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Sales\Enums\QuotationStatus;

/**
 * Quotation Model for AutoERP
 *
 * Manages sales quotations with multi-currency support,
 * conversion tracking, and comprehensive pricing capabilities.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_number
 * @property string $customer_id
 * @property \Illuminate\Support\Carbon $quote_date
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property QuotationStatus $status
 * @property string $currency
 * @property float $exchange_rate
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property string|null $terms_and_conditions
 * @property string|null $notes
 * @property array|null $custom_fields
 * @property string|null $converted_to_order_id
 * @property \Illuminate\Support\Carbon|null $converted_at
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Quotation extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'quotations';

    protected $fillable = [
        'tenant_id',
        'quote_number',
        'customer_id',
        'quote_date',
        'valid_until',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'terms_and_conditions',
        'notes',
        'custom_fields',
        'converted_to_order_id',
        'converted_at',
        'created_by',
    ];

    protected $casts = [
        'status' => QuotationStatus::class,
        'quote_date' => 'datetime',
        'valid_until' => 'datetime',
        'converted_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'USD',
        'exchange_rate' => 1.000000,
        'subtotal' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
    ];

    /**
     * Get the customer for this quotation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the converted sales order if exists.
     */
    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_to_order_id');
    }

    /**
     * Get all line items for this quotation.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id');
    }

    /**
     * Check if quotation is still valid (not expired).
     */
    public function isValid(): bool
    {
        if (! $this->valid_until) {
            return true;
        }

        return now()->lte($this->valid_until);
    }

    /**
     * Check if quotation can be converted to order.
     */
    public function canConvertToOrder(): bool
    {
        return $this->status->value === 'accepted'
            && $this->isValid()
            && is_null($this->converted_to_order_id);
    }

    /**
     * Mark quotation as sent to customer.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => QuotationStatus::Sent]);
    }

    /**
     * Accept the quotation.
     */
    public function accept(): void
    {
        $this->update(['status' => QuotationStatus::Accepted]);
    }

    /**
     * Reject the quotation.
     */
    public function reject(): void
    {
        $this->update(['status' => QuotationStatus::Rejected]);
    }

    /**
     * Mark quotation as expired.
     */
    public function expire(): void
    {
        $this->update(['status' => QuotationStatus::Expired]);
    }

    /**
     * Calculate and update totals based on line items.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items;

        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $taxAmount = $items->sum('tax_amount');
        $discountAmount = $this->discount_amount;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Scope query to draft quotations.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', QuotationStatus::Draft);
    }

    /**
     * Scope query to sent quotations.
     */
    public function scopeSent($query)
    {
        return $query->where('status', QuotationStatus::Sent);
    }

    /**
     * Scope query to accepted quotations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', QuotationStatus::Accepted);
    }

    /**
     * Scope query to valid (not expired) quotations.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now());
        });
    }

    /**
     * Scope query to expired quotations.
     */
    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
            ->whereNotNull('valid_until');
    }
}
