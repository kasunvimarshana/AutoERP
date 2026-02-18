<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Inventory\Database\Factories\WarehouseFactory;

/**
 * Warehouse Entity
 *
 * Represents a physical warehouse or storage location for inventory.
 */
class Warehouse extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'warehouse_type',
        'manager_id',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get stock locations in this warehouse.
     */
    public function stockLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Get stock levels in this warehouse.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Get stock movements in this warehouse.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if warehouse is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get total products in warehouse.
     */
    public function getTotalProducts(): int
    {
        return $this->stockLevels()
            ->where('quantity_on_hand', '>', 0)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * Get total quantity of all products in warehouse.
     */
    public function getTotalQuantity(): float
    {
        return $this->stockLevels()->sum('quantity_on_hand');
    }

    /**
     * Get warehouse utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        $totalLocations = $this->stockLocations()->count();

        if ($totalLocations === 0) {
            return 0.0;
        }

        $occupiedLocations = $this->stockLocations()
            ->whereHas('stockLevels', function ($query) {
                $query->where('quantity_on_hand', '>', 0);
            })
            ->count();

        return ($occupiedLocations / $totalLocations) * 100;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WarehouseFactory
    {
        return WarehouseFactory::new();
    }
}
