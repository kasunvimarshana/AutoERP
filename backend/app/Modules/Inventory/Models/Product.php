<?php

namespace App\Modules\Inventory\Models;

use App\Enums\ProductType;
use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * Represents a product in the inventory system.
 * Supports multiple product types: inventory, service, combo, bundle.
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Inventory\ProductFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'type',
        'description',
        'barcode',
        'buying_price',
        'selling_price',
        'mrp',
        'track_inventory',
        'track_batch',
        'track_serial',
        'track_expiry',
        'reorder_level',
        'min_stock_level',
        'max_stock_level',
        'category_id',
        'brand_id',
        'unit_id',
        'tax_id',
        'is_active',
        'attributes',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ProductType::class,
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'track_inventory' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'track_expiry' => 'boolean',
        'reorder_level' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'is_active' => 'boolean',
        'attributes' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the tenant that owns the product
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the stock ledger entries for the product
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if product requires stock tracking
     */
    public function requiresStockTracking(): bool
    {
        return $this->track_inventory && $this->type->requiresStockTracking();
    }

    /**
     * Check if product is a physical inventory item
     */
    public function isPhysical(): bool
    {
        return $this->type->isPhysical();
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get current stock balance
     */
    public function getCurrentStock(?int $warehouseId = null): float
    {
        $query = $this->stockLedgers();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity');
    }

    /**
     * Check if product is below reorder level
     */
    public function isBelowReorderLevel(?int $warehouseId = null): bool
    {
        if (! $this->reorder_level) {
            return false;
        }

        return $this->getCurrentStock($warehouseId) <= $this->reorder_level;
    }

    /**
     * Get profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->buying_price <= 0) {
            return 0;
        }

        return (($this->selling_price - $this->buying_price) / $this->buying_price) * 100;
    }
}
