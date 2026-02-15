<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Models;

use App\Modules\Product\Models\Product;
use App\Modules\MasterData\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkOrderItem Model
 * 
 * Represents materials/components consumed in a work order
 */
class WorkOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'product_id',
        'bom_item_id',
        'planned_quantity',
        'allocated_quantity',
        'consumed_quantity',
        'returned_quantity',
        'unit_id',
        'unit_cost',
        'total_cost',
        'scrap_percentage',
        'status',
        'sequence',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'allocated_quantity' => 'decimal:4',
        'consumed_quantity' => 'decimal:4',
        'returned_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'scrap_percentage' => 'decimal:2',
        'sequence' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the work order this item belongs to
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get the product/material
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the BOM item reference
     */
    public function bomItem(): BelongsTo
    {
        return $this->belongsTo(BOMItem::class, 'bom_item_id');
    }

    /**
     * Get the unit of measure
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Get remaining quantity to allocate
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->planned_quantity - $this->allocated_quantity);
    }

    /**
     * Get shortfall quantity (consumed more than planned)
     */
    public function getShortfallQuantityAttribute(): float
    {
        return max(0, $this->consumed_quantity - $this->planned_quantity);
    }

    /**
     * Calculate consumption percentage
     */
    public function getConsumptionPercentageAttribute(): float
    {
        if ($this->planned_quantity <= 0) {
            return 0;
        }

        return round(($this->consumed_quantity / $this->planned_quantity) * 100, 2);
    }

    /**
     * Check if item is fully consumed
     */
    public function isFullyConsumed(): bool
    {
        return $this->consumed_quantity >= $this->planned_quantity;
    }

    /**
     * Check if item is allocated
     */
    public function isAllocated(): bool
    {
        return $this->allocated_quantity >= $this->planned_quantity;
    }
}
