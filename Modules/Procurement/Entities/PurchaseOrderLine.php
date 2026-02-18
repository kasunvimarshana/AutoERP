<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Procurement\Database\Factories\PurchaseOrderLineFactory;

/**
 * Purchase Order Line Entity
 *
 * Represents an individual line item in a purchase order.
 */
class PurchaseOrderLine extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_total',
        'received_quantity',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('Modules\Inventory\Entities\Product', 'product_id');
    }

    /**
     * Calculate line total and tax.
     */
    public function calculateTotal(): void
    {
        $lineTotal = $this->quantity * $this->unit_price;
        $taxAmount = $lineTotal * ($this->tax_rate / 100);

        $this->update([
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ]);
    }

    /**
     * Update received quantity.
     */
    public function updateReceivedQuantity(float $quantity): void
    {
        $this->increment('received_quantity', $quantity);
    }

    /**
     * Check if fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Get remaining quantity.
     */
    public function getRemainingQuantity(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseOrderLineFactory
    {
        return PurchaseOrderLineFactory::new();
    }
}
