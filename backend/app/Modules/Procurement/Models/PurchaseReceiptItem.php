<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;

class PurchaseReceiptItem extends Model
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
        'purchase_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'variant_id',
        'ordered_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'unit_cost',
        'batch_number',
        'serial_number',
        'lot_number',
        'expiry_date',
        'manufacturing_date',
        'quality_status',
        'rejection_reason',
        'notes',
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
        'ordered_quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'accepted_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the receipt that owns the item.
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    /**
     * Get the purchase order item.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant for the item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the user who created the item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if item is fully accepted.
     */
    public function isFullyAccepted(): bool
    {
        return $this->accepted_quantity >= $this->received_quantity;
    }

    /**
     * Check if item is partially accepted.
     */
    public function isPartiallyAccepted(): bool
    {
        return $this->accepted_quantity > 0 && $this->accepted_quantity < $this->received_quantity;
    }

    /**
     * Check if item is fully rejected.
     */
    public function isFullyRejected(): bool
    {
        return $this->rejected_quantity >= $this->received_quantity;
    }

    /**
     * Check if item passed quality check.
     */
    public function passedQualityCheck(): bool
    {
        return $this->quality_status === 'passed';
    }

    /**
     * Check if item failed quality check.
     */
    public function failedQualityCheck(): bool
    {
        return $this->quality_status === 'failed';
    }

    /**
     * Calculate total cost.
     */
    public function calculateTotalCost(): float
    {
        return round($this->accepted_quantity * $this->unit_cost, 2);
    }
}
