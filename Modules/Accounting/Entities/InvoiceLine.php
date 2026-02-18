<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Database\Factories\InvoiceLineFactory;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;

/**
 * Invoice Line Entity
 *
 * Represents a line item in an invoice.
 */
class InvoiceLine extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'uuid',
        'invoice_id',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo('Modules\Inventory\Entities\Product', 'product_id');
    }

    /**
     * Get the revenue account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Calculate line totals.
     */
    public function calculateTotals(): void
    {
        // Calculate subtotal
        $this->subtotal = $this->quantity * $this->unit_price;

        // Apply discount
        if ($this->discount_percent > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percent / 100);
        }
        $subtotalAfterDiscount = $this->subtotal - $this->discount_amount;

        // Calculate tax
        if ($this->tax_rate > 0) {
            $this->tax_amount = $subtotalAfterDiscount * ($this->tax_rate / 100);
        }

        // Calculate total
        $this->total = $subtotalAfterDiscount + $this->tax_amount;
    }

    /**
     * Boot method.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($line) {
            $line->calculateTotals();
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InvoiceLineFactory
    {
        return InvoiceLineFactory::new();
    }
}
