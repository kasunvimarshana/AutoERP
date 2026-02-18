<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Procurement\Database\Factories\PurchaseOrderFactory;

/**
 * Purchase Order Entity
 *
 * Represents a purchase order sent to a supplier.
 */
class PurchaseOrder extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'order_number',
        'purchase_requisition_id',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'payment_status',
        'payment_terms',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'notes',
        'internal_notes',
        'terms_and_conditions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['notes', 'internal_notes', 'terms_and_conditions'];

    /**
     * Get the supplier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase requisition.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    /**
     * Get the purchase order lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Get the goods receipts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total');
        $taxAmount = $this->lines()->sum('tax_amount');

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount + $this->shipping_amount - $this->discount_amount,
        ]);
    }

    /**
     * Send the purchase order.
     */
    public function send(): void
    {
        $this->update(['status' => 'sent']);

        event(new \Modules\Procurement\Events\PurchaseOrderCreated($this));
    }

    /**
     * Confirm the purchase order.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Close the purchase order.
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Check if order is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if order is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }
}
