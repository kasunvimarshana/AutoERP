<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Procurement\Database\Factories\GoodsReceiptFactory;

/**
 * Goods Receipt Entity
 *
 * Represents the receipt of goods from a supplier.
 */
class GoodsReceipt extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'receipt_number',
        'purchase_order_id',
        'received_date',
        'received_by',
        'status',
        'matched_status',
        'warehouse_id',
        'notes',
        'internal_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'received_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['notes', 'internal_notes'];

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
     * Get the receiver.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receiver()
    {
        return $this->belongsTo('App\Models\User', 'received_by');
    }

    /**
     * Get the warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo('Modules\Inventory\Entities\Warehouse', 'warehouse_id');
    }

    /**
     * Confirm the goods receipt.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);

        event(new \Modules\Procurement\Events\GoodsReceived($this));
    }

    /**
     * Perform 3-way matching.
     */
    public function performThreeWayMatch(): bool
    {
        // Logic for matching PO, Receipt, and Invoice
        // This is a simplified version
        $purchaseOrder = $this->purchaseOrder;

        if (! $purchaseOrder) {
            return false;
        }

        // Check if all lines are received
        $allReceived = true;
        foreach ($purchaseOrder->lines as $line) {
            if (! $line->isFullyReceived()) {
                $allReceived = false;
                break;
            }
        }

        if ($allReceived) {
            $this->update(['matched_status' => 'matched']);

            return true;
        }

        $this->update(['matched_status' => 'partial']);

        return false;
    }

    /**
     * Check if receipt is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if receipt is matched.
     */
    public function isMatched(): bool
    {
        return $this->matched_status === 'matched';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GoodsReceiptFactory
    {
        return GoodsReceiptFactory::new();
    }
}
