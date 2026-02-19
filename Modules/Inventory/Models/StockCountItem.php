<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * StockCountItem Model
 *
 * Individual count line items for physical inventory counts.
 * Tracks system quantity, counted quantity, and variance for each product.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $stock_count_id
 * @property string $product_id
 * @property string|null $location_id
 * @property string|null $batch_lot_id
 * @property string $system_quantity
 * @property string|null $counted_quantity
 * @property string|null $variance
 * @property string|null $unit_cost
 * @property string|null $variance_value
 * @property string|null $notes
 * @property string|null $counted_by
 * @property \Carbon\Carbon|null $counted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StockCountItem extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'stock_count_id',
        'product_id',
        'location_id',
        'batch_lot_id',
        'system_quantity',
        'counted_quantity',
        'variance',
        'unit_cost',
        'variance_value',
        'notes',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:6',
        'counted_quantity' => 'decimal:6',
        'variance' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'variance_value' => 'decimal:6',
        'counted_at' => 'datetime',
    ];

    /**
     * Get the stock count that owns this item.
     */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    /**
     * Get the product for this count item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location for this count item.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * Get the batch lot for this count item.
     */
    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLot::class);
    }

    /**
     * Calculate variance (counted_quantity - system_quantity).
     */
    public function calculateVariance(): string
    {
        if ($this->counted_quantity === null) {
            return '0.000000';
        }

        return bcsub((string) $this->counted_quantity, (string) $this->system_quantity, 6);
    }

    /**
     * Calculate variance value (variance * unit_cost).
     */
    public function calculateVarianceValue(): ?string
    {
        if ($this->unit_cost === null || $this->variance === null) {
            return null;
        }

        return bcmul((string) $this->variance, (string) $this->unit_cost, 6);
    }

    /**
     * Check if there is a variance.
     */
    public function hasVariance(): bool
    {
        return $this->variance !== null && bccomp((string) $this->variance, '0', 6) != 0;
    }

    /**
     * Check if variance is positive (surplus).
     */
    public function isSurplus(): bool
    {
        return $this->variance !== null && bccomp((string) $this->variance, '0', 6) > 0;
    }

    /**
     * Check if variance is negative (shortage).
     */
    public function isShortage(): bool
    {
        return $this->variance !== null && bccomp((string) $this->variance, '0', 6) < 0;
    }

    /**
     * Get variance percentage relative to system quantity.
     */
    public function getVariancePercentage(): ?string
    {
        if ($this->variance === null || bccomp((string) $this->system_quantity, '0', 6) == 0) {
            return null;
        }

        $percentage = bcdiv(
            bcmul((string) $this->variance, '100', 6),
            (string) $this->system_quantity,
            2
        );

        return $percentage;
    }
}
