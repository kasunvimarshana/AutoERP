<?php

declare(strict_types=1);

namespace Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductVariant;
use Modules\Inventory\Models\WarehouseLocation;

/**
 * Goods Receipt Line Item Model
 *
 * Represents individual products received in a goods receipt.
 * Tracks quantities, inspection status, and quality control.
 */
class GoodsReceiptLineItem extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'goods_receipt_line_items';

    protected $fillable = [
        'tenant_id',
        'goods_receipt_id',
        'purchase_order_line_item_id',
        'product_id',
        'variant_id',
        'location_id',
        'ordered_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'unit_of_measure',
        'unit_cost',
        'total_cost',
        'batch_number',
        'serial_number',
        'expiry_date',
        'inspection_status',
        'inspection_notes',
        'rejection_reason',
        'custom_fields',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'accepted_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'custom_fields' => 'array',
    ];

    /**
     * Get the goods receipt
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * Get the purchase order line item
     */
    public function purchaseOrderLineItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLineItem::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the warehouse location
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    /**
     * Calculate pending inspection quantity
     */
    public function getPendingInspectionQuantityAttribute(): float
    {
        return $this->received_quantity - ($this->accepted_quantity + $this->rejected_quantity);
    }

    /**
     * Check if all received quantity is inspected
     */
    public function isFullyInspected(): bool
    {
        return ($this->accepted_quantity + $this->rejected_quantity) >= $this->received_quantity;
    }

    /**
     * Check if inspection passed
     */
    public function inspectionPassed(): bool
    {
        return $this->inspection_status === 'passed';
    }

    /**
     * Check if inspection failed
     */
    public function inspectionFailed(): bool
    {
        return $this->inspection_status === 'failed';
    }

    /**
     * Get acceptance rate percentage
     */
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->received_quantity == 0) {
            return 0;
        }

        return ($this->accepted_quantity / $this->received_quantity) * 100;
    }

    /**
     * Boot method
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-calculate total cost
            if ($model->received_quantity && $model->unit_cost) {
                $model->total_cost = $model->received_quantity * $model->unit_cost;
            }
        });
    }
}
