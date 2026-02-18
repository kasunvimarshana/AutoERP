<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Inventory\Database\Factories\StockLocationFactory;

/**
 * Stock Location Entity
 *
 * Represents a specific location within a warehouse (bin, aisle, rack, etc.)
 * Supports hierarchical structure for complex warehouse layouts.
 */
class StockLocation extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'code',
        'name',
        'location_type',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'capacity',
        'capacity_unit',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the warehouse this location belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the parent location.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'parent_id');
    }

    /**
     * Get child locations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'parent_id');
    }

    /**
     * Get stock levels at this location.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Get stock movements for this location.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if location is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get full location code (including hierarchy).
     */
    public function getFullCode(): string
    {
        $parts = array_filter([
            $this->aisle,
            $this->rack,
            $this->shelf,
            $this->bin,
        ]);

        return implode('-', $parts) ?: $this->code;
    }

    /**
     * Get current capacity usage.
     */
    public function getCurrentCapacityUsage(): float
    {
        return $this->stockLevels()->sum('quantity_on_hand');
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): float
    {
        if (! $this->capacity) {
            return PHP_FLOAT_MAX;
        }

        return max(0, $this->capacity - $this->getCurrentCapacityUsage());
    }

    /**
     * Check if location has available capacity.
     */
    public function hasCapacity(float $quantity = 1): bool
    {
        if (! $this->capacity) {
            return true;
        }

        return $this->getRemainingCapacity() >= $quantity;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StockLocationFactory
    {
        return StockLocationFactory::new();
    }
}
