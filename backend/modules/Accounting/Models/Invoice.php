<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\InvoiceStatus;
use Modules\Core\Models\BaseModel;

/**
 * Invoice Model
 *
 * Represents a billing invoice.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $invoice_number
 * @property string|null $sales_order_id
 * @property string|null $customer_id
 * @property InvoiceStatus $status
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string|null $billing_address
 * @property string $currency_code
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property float $paid_amount
 * @property float $balance_due
 * @property string|null $notes
 * @property string|null $terms
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Invoice extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'sales_order_id',
        'customer_id',
        'status',
        'invoice_date',
        'due_date',
        'billing_address',
        'currency_code',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'notes',
        'terms',
        'sent_at',
        'paid_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * Get the customer associated with this invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sales\Models\Customer::class, 'customer_id');
    }

    /**
     * Get the sales order associated with this invoice.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sales\Models\SalesOrder::class, 'sales_order_id');
    }

    /**
     * Get payment allocations for this invoice.
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'invoice_id');
    }
}
