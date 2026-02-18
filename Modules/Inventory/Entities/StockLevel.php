<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Inventory\Database\Factories\StockLevelFactory;

/**
 * Stock Level Entity
 *
 * Represents the quantity of a product at a specific warehouse location.
 * Tracks on-hand, available, and reserved quantities.
 */
class StockLevel extends Model
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
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'unit_cost',
        'currency',
        'valuation_method',
        'last_recount_date',
        'last_movement_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'quantity_reserved' => 'decimal:3',
        'quantity_available' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'last_recount_date' => 'datetime',
        'last_movement_date' => 'datetime',
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
     * Update available quantity.
     * Available = On Hand - Reserved
     */
    public function updateAvailableQuantity(): void
    {
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->save();
    }

    /**
     * Check if stock is available.
     */
    public function hasAvailableStock(float $quantity = 1): bool
    {
        return $this->quantity_available >= $quantity;
    }

    /**
     * Reserve quantity.
     */
    public function reserve(float $quantity): bool
    {
        if (! $this->hasAvailableStock($quantity)) {
            return false;
        }

        $this->quantity_reserved += $quantity;
        $this->updateAvailableQuantity();

        return true;
    }

    /**
     * Release reserved quantity.
     */
    public function release(float $quantity): void
    {
        $this->quantity_reserved = max(0, $this->quantity_reserved - $quantity);
        $this->updateAvailableQuantity();
    }

    /**
     * Add quantity.
     */
    public function addQuantity(float $quantity, ?float $unitCost = null): void
    {
        $this->quantity_on_hand += $quantity;

        if ($unitCost !== null) {
            $this->unit_cost = $unitCost;
        }

        $this->last_movement_date = now();
        $this->updateAvailableQuantity();
    }

    /**
     * Remove quantity.
     */
    public function removeQuantity(float $quantity): bool
    {
        if ($this->quantity_on_hand < $quantity) {
            return false;
        }

        $this->quantity_on_hand -= $quantity;
        $this->last_movement_date = now();
        $this->updateAvailableQuantity();

        return true;
    }

    /**
     * Get inventory value.
     */
    public function getInventoryValue(): float
    {
        return $this->quantity_on_hand * $this->unit_cost;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockLevelFactory
    {
        return StockLevelFactory::new();
    }
}
