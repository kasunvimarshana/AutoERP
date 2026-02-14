<?php

namespace App\Modules\Inventory\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Inventory",
 *     type="object",
 *     title="Inventory",
 *     description="Inventory model for current stock levels",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="product_variant_id", type="string", format="uuid"),
 *     @OA\Property(property="quantity_on_hand", type="integer", example=100),
 *     @OA\Property(property="quantity_reserved", type="integer", example=10),
 *     @OA\Property(property="quantity_available", type="integer", example=90),
 *     @OA\Property(property="reorder_level", type="integer", example=20),
 *     @OA\Property(property="reorder_quantity", type="integer", example=50),
 *     @OA\Property(property="last_stock_date", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Inventory extends BaseModel
{
    protected $table = 'inventory';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'reorder_level',
        'reorder_quantity',
        'last_stock_date',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'last_stock_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['quantity_available'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'inventory_id');
    }

    public function getQuantityAvailableAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function needsReorder(): bool
    {
        return $this->isLowStock() && $this->reorder_level > 0;
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0);
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}
