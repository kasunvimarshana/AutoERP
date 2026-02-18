<?php

declare(strict_types=1);

namespace Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\HasAudit;
use Modules\Inventory\Models\Warehouse;
use Modules\Purchasing\Enums\GoodsReceiptStatus;

/**
 * Goods Receipt Model
 *
 * Represents the physical receipt of goods from a purchase order.
 * Supports inspection, acceptance, and rejection workflows.
 */
class GoodsReceipt extends BaseModel
{
    use HasAudit;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'tenant_id',
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'received_by',
        'delivery_note_number',
        'vehicle_number',
        'notes',
        'custom_fields',
        'created_by',
    ];

    protected $casts = [
        'status' => GoodsReceiptStatus::class,
        'receipt_date' => 'date',
        'custom_fields' => 'array',
    ];

    protected $with = [];

    /**
     * Get the purchase order for this receipt
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the warehouse where goods are received
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get line items for this receipt
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptLineItem::class);
    }

    /**
     * Check if receipt can be edited
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if receipt is final
     */
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Get total received quantity across all line items
     */
    public function getTotalReceivedQuantityAttribute(): float
    {
        return $this->lineItems()->sum('received_quantity');
    }

    /**
     * Get total accepted quantity across all line items
     */
    public function getTotalAcceptedQuantityAttribute(): float
    {
        return $this->lineItems()->sum('accepted_quantity');
    }

    /**
     * Get total rejected quantity across all line items
     */
    public function getTotalRejectedQuantityAttribute(): float
    {
        return $this->lineItems()->sum('rejected_quantity');
    }

    /**
     * Get total cost of received goods
     */
    public function getTotalCostAttribute(): float
    {
        return $this->lineItems()->sum('total_cost');
    }

    /**
     * Boot method
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->receipt_number)) {
                $model->receipt_number = self::generateReceiptNumber();
            }
        });
    }

    /**
     * Generate unique receipt number
     */
    protected static function generateReceiptNumber(): string
    {
        $prefix = 'GR';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return "{$prefix}-{$date}-{$random}";
    }
}
