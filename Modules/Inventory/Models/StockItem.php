<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * StockItem Model
 *
 * Represents product inventory records with current quantities.
 * Tracks available, reserved, and reorder point quantities per product per warehouse.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string|null $location_id
 * @property string $quantity
 * @property string $reserved_quantity
 * @property string $available_quantity
 * @property string|null $reorder_point
 * @property string|null $reorder_quantity
 * @property string|null $minimum_quantity
 * @property string|null $maximum_quantity
 * @property string $average_cost
 * @property string|null $last_stock_count_date
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StockItem extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'reorder_point',
        'reorder_quantity',
        'minimum_quantity',
        'maximum_quantity',
        'average_cost',
        'last_stock_count_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'reserved_quantity' => 'decimal:6',
        'available_quantity' => 'decimal:6',
        'reorder_point' => 'decimal:6',
        'reorder_quantity' => 'decimal:6',
        'minimum_quantity' => 'decimal:6',
        'maximum_quantity' => 'decimal:6',
        'average_cost' => 'decimal:6',
        'last_stock_count_date' => 'date',
    ];

    /**
     * Get the product for this stock item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this stock item.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location for this stock item.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * Get all stock movements for this product.
     * Note: Returns movements across all warehouses. Use helper methods for warehouse-specific filtering.
     */
    public function stockMovementsFrom(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id');
    }

    /**
     * Get all stock movements for this product.
     * Note: Returns movements across all warehouses. Use helper methods for warehouse-specific filtering.
     */
    public function stockMovementsTo(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id');
    }

    /**
     * Get all batch lots for this product.
     * Note: Returns batch lots across all warehouses. Use getWarehouseBatchLots() for warehouse-specific results.
     */
    public function batchLots(): HasMany
    {
        return $this->hasMany(BatchLot::class, 'product_id', 'product_id');
    }

    /**
     * Get all serial numbers for this product.
     * Note: Returns serial numbers across all warehouses. Use getWarehouseSerialNumbers() for warehouse-specific results.
     */
    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class, 'product_id', 'product_id');
    }

    /**
     * Get batch lots filtered by this stock item's warehouse.
     */
    public function getWarehouseBatchLots()
    {
        return $this->batchLots()->where('warehouse_id', $this->warehouse_id)->get();
    }

    /**
     * Get serial numbers filtered by this stock item's warehouse.
     */
    public function getWarehouseSerialNumbers()
    {
        return $this->serialNumbers()->where('warehouse_id', $this->warehouse_id)->get();
    }

    /**
     * Get stock movements from this stock item's warehouse.
     */
    public function getWarehouseStockMovementsFrom()
    {
        return $this->stockMovementsFrom()->where('from_warehouse_id', $this->warehouse_id)->get();
    }

    /**
     * Get stock movements to this stock item's warehouse.
     */
    public function getWarehouseStockMovementsTo()
    {
        return $this->stockMovementsTo()->where('to_warehouse_id', $this->warehouse_id)->get();
    }

    /**
     * Calculate available quantity (quantity - reserved_quantity).
     */
    public function calculateAvailableQuantity(): string
    {
        return bcsub((string) $this->quantity, (string) $this->reserved_quantity, 6);
    }

    /**
     * Check if stock is below reorder point.
     */
    public function isBelowReorderPoint(): bool
    {
        if ($this->reorder_point === null) {
            return false;
        }

        return bccomp((string) $this->available_quantity, (string) $this->reorder_point, 6) < 0;
    }

    /**
     * Check if stock is below minimum quantity.
     */
    public function isBelowMinimum(): bool
    {
        if ($this->minimum_quantity === null) {
            return false;
        }

        return bccomp((string) $this->quantity, (string) $this->minimum_quantity, 6) < 0;
    }

    /**
     * Check if stock is above maximum quantity.
     */
    public function isAboveMaximum(): bool
    {
        if ($this->maximum_quantity === null) {
            return false;
        }

        return bccomp((string) $this->quantity, (string) $this->maximum_quantity, 6) > 0;
    }

    /**
     * Get the suggested reorder quantity.
     */
    public function getSuggestedReorderQuantity(): ?string
    {
        if ($this->reorder_quantity !== null) {
            return (string) $this->reorder_quantity;
        }

        if ($this->maximum_quantity !== null && $this->quantity !== null) {
            return bcsub((string) $this->maximum_quantity, (string) $this->quantity, 6);
        }

        return null;
    }

    /**
     * Update average cost using weighted average method.
     */
    public function updateAverageCost(string $newQuantity, string $newCost): void
    {
        $currentValue = bcmul((string) $this->quantity, (string) $this->average_cost, 6);
        $newValue = bcmul($newQuantity, $newCost, 6);
        $totalValue = bcadd($currentValue, $newValue, 6);
        $totalQuantity = bcadd((string) $this->quantity, $newQuantity, 6);

        if (bccomp($totalQuantity, '0', 6) > 0) {
            $this->average_cost = bcdiv($totalValue, $totalQuantity, 6);
        }
    }
}
