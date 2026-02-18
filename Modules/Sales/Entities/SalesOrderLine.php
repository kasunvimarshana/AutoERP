<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Sales\Database\Factories\SalesOrderLineFactory;

/**
 * Sales Order Line Entity
 *
 * Represents a line item in a sales order.
 * Contains product, quantity, pricing, and tax information.
 */
class SalesOrderLine extends Model
{
    use Auditable, HasFactory, Tenantable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales_order_lines';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'line_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('Modules\Inventory\Entities\Product');
    }

    /**
     * Calculate line totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discountAmount = $this->discount_percent > 0
            ? $subtotal * ($this->discount_percent / 100)
            : $this->discount_amount;

        $amountAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $amountAfterDiscount * ($this->tax_percent / 100);
        $lineTotal = $amountAfterDiscount + $taxAmount;

        $this->update([
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            if ($line->isDirty(['quantity', 'unit_price', 'discount_percent', 'discount_amount', 'tax_percent'])) {
                $subtotal = $line->quantity * $line->unit_price;
                $discountAmount = $line->discount_percent > 0
                    ? $subtotal * ($line->discount_percent / 100)
                    : $line->discount_amount;

                $amountAfterDiscount = $subtotal - $discountAmount;
                $taxAmount = $amountAfterDiscount * ($line->tax_percent / 100);

                $line->discount_amount = $discountAmount;
                $line->tax_amount = $taxAmount;
                $line->line_total = $amountAfterDiscount + $taxAmount;
            }
        });

        static::saved(function ($line) {
            $line->salesOrder->calculateTotals();
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SalesOrderLineFactory
    {
        return SalesOrderLineFactory::new();
    }
}
