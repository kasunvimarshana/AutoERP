<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Inventory\Database\Factories\StockMovementFactory;

/**
 * Stock Movement Entity
 *
 * Represents a stock transaction (receipt, shipment, transfer, adjustment).
 * Tracks all inventory movements with full audit trail.
 */
class StockMovement extends Model
{
    use Auditable, HasFactory, HasUuid, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'stock_location_id',
        'movement_type',
        'movement_number',
        'quantity',
        'unit_cost',
        'currency',
        'movement_date',
        'reference_type',
        'reference_id',
        'reference_number',
        'reason',
        'notes',
        'from_warehouse_id',
        'from_location_id',
        'to_warehouse_id',
        'to_location_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'movement_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the stock location.
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    /**
     * Get the source warehouse (for transfers).
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the source location (for transfers).
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    /**
     * Get the destination warehouse (for transfers).
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the destination location (for transfers).
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    /**
     * Check if movement is an inbound movement.
     */
    public function isInbound(): bool
    {
        return in_array($this->movement_type, ['receipt', 'return', 'adjustment_in', 'transfer_in']);
    }

    /**
     * Check if movement is an outbound movement.
     */
    public function isOutbound(): bool
    {
        return in_array($this->movement_type, ['shipment', 'consumption', 'adjustment_out', 'transfer_out']);
    }

    /**
     * Check if movement is a transfer.
     */
    public function isTransfer(): bool
    {
        return in_array($this->movement_type, ['transfer_in', 'transfer_out']);
    }

    /**
     * Check if movement is an adjustment.
     */
    public function isAdjustment(): bool
    {
        return in_array($this->movement_type, ['adjustment_in', 'adjustment_out']);
    }

    /**
     * Get movement direction (+1 for inbound, -1 for outbound).
     */
    public function getDirection(): int
    {
        return $this->isInbound() ? 1 : -1;
    }

    /**
     * Get signed quantity (positive for inbound, negative for outbound).
     */
    public function getSignedQuantity(): float
    {
        return $this->quantity * $this->getDirection();
    }

    /**
     * Get movement value.
     */
    public function getMovementValue(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockMovementFactory
    {
        return StockMovementFactory::new();
    }
}
