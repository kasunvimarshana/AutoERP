<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Organization\Models\Branch;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'type',
        'category',
        'brand',
        'unit',
        'is_variant_product',
        'track_stock',
        'track_batch',
        'track_serial',
        'min_stock_level',
        'max_stock_level',
        'reorder_level',
        'reorder_quantity',
        'cost_price',
        'selling_price',
        'tax_rate',
        'tax_inclusive',
        'weight',
        'dimensions',
        'images',
        'attributes',
        'is_active',
        'is_purchasable',
        'is_sellable',
        'metadata',
    ];

    protected $casts = [
        'is_variant_product' => 'boolean',
        'track_stock' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'tax_inclusive' => 'boolean',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'images' => 'json',
        'attributes' => 'json',
        'is_active' => 'boolean',
        'is_purchasable' => 'boolean',
        'is_sellable' => 'boolean',
        'metadata' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the product
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch that owns the product
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the variants for the product
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the batches for the product
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get the stock ledger entries for the product
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if product has variants
     */
    public function hasVariants(): bool
    {
        return $this->is_variant_product && $this->variants()->exists();
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(int $branchId = null): bool
    {
        $query = $this->stockLedgers();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $balance = $query->sum('quantity');
        
        return $balance > 0;
    }

    /**
     * Get current stock balance
     */
    public function getStockBalance(int $branchId = null): float
    {
        $query = $this->stockLedgers();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Check if reorder is needed
     */
    public function needsReorder(int $branchId = null): bool
    {
        if (!$this->reorder_level) {
            return false;
        }

        $balance = $this->getStockBalance($branchId);
        
        return $balance <= $this->reorder_level;
    }
}
