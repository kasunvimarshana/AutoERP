<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'type',
        'status',
        'category',
        'subcategory',
        'brand',
        'cost_price',
        'selling_price',
        'currency',
        'unit_of_measure',
        'unit_weight',
        'weight_unit',
        'track_inventory',
        'track_batch',
        'track_serial',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'is_taxable',
        'tax_category',
        'images',
        'attachments',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'unit_weight' => 'decimal:3',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'track_inventory' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'is_taxable' => 'boolean',
        'images' => 'array',
        'attachments' => 'array',
        'attributes' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the stock ledger entries for the product.
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if product is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if product tracks inventory.
     */
    public function tracksInventory(): bool
    {
        return $this->track_inventory;
    }

    /**
     * Get current stock for a warehouse.
     */
    public function getStockInWarehouse(int $warehouseId): float
    {
        return $this->stockLedgers()
            ->where('warehouse_id', $warehouseId)
            ->latest('id')
            ->value('balance_after') ?? 0;
    }

    /**
     * Get total stock across all warehouses.
     */
    public function getTotalStock(): float
    {
        return $this->stockLedgers()
            ->selectRaw('warehouse_id, MAX(id) as last_id')
            ->groupBy('warehouse_id')
            ->get()
            ->sum(function ($group) {
                return StockLedger::find($group->last_id)?->balance_after ?? 0;
            });
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
