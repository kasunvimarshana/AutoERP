<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'weight',
        'dimensions',
        'image',
        'attributes',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'attributes' => 'json',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the variant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the batches for the variant
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get the stock ledger entries for the variant
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
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
     * Check if variant is in stock
     */
    public function isInStock(int $branchId = null): bool
    {
        return $this->getStockBalance($branchId) > 0;
    }

    /**
     * Get full name including parent product
     */
    public function getFullNameAttribute(): string
    {
        return $this->product->name . ' - ' . $this->name;
    }
}
