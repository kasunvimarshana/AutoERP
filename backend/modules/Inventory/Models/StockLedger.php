<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Inventory\Enums\TransactionType;

/**
 * Stock Ledger Model
 *
 * Append-only immutable transaction log for all stock movements.
 * This is the single source of truth for inventory tracking.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $warehouse_id
 * @property string|null $location_id
 * @property TransactionType $transaction_type
 * @property float $quantity
 * @property string|null $uom_id
 * @property string|null $batch_number
 * @property string|null $serial_number
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property float|null $unit_cost
 * @property float|null $total_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 */
class StockLedger extends BaseModel
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * We only track creation, never updates (append-only)
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_ledger';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'location_id',
        'transaction_type',
        'quantity',
        'uom_id',
        'batch_number',
        'serial_number',
        'reference_type',
        'reference_id',
        'unit_cost',
        'total_cost',
        'notes',
        'transaction_date',
        'created_by',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_type' => TransactionType::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array<int, string>
     */
    protected $visible = [
        'id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'location_id',
        'transaction_type',
        'quantity',
        'uom_id',
        'batch_number',
        'serial_number',
        'reference_type',
        'reference_id',
        'unit_cost',
        'total_cost',
        'notes',
        'transaction_date',
        'created_by',
        'created_at',
        'product',
        'warehouse',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent updates and deletes - this is an append-only ledger
        static::updating(function () {
            throw new \RuntimeException('Stock ledger entries cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Stock ledger entries cannot be deleted.');
        });
    }

    /**
     * Get the product that owns the stock ledger entry.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the product variant if applicable.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the warehouse for this entry.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the location if applicable.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Scope to filter by product.
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to filter by warehouse.
     */
    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to filter by transaction type.
     */
    public function scopeOfType($query, TransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Get the effective quantity (considering transaction type multiplier).
     */
    public function getEffectiveQuantityAttribute(): float
    {
        return $this->quantity * $this->transaction_type->multiplier();
    }
}
