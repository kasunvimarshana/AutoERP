<?php

namespace App\Modules\Product\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *     title="ProductVariant",
 *     description="Product variant model for SKU variants",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="product_id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="sku", type="string", example="PROD-001-RED-L"),
 *     @OA\Property(property="variant_name", type="string", example="Red - Large"),
 *     @OA\Property(property="attributes", type="object", example={"color": "Red", "size": "Large"}),
 *     @OA\Property(property="cost_price", type="number", format="float", example=50.00),
 *     @OA\Property(property="selling_price", type="number", format="float", example=100.00),
 *     @OA\Property(property="barcode", type="string", example="1234567890123"),
 *     @OA\Property(property="weight", type="number", format="float", example=1.5),
 *     @OA\Property(property="dimensions", type="object", example={"length": 10, "width": 5, "height": 3}),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class ProductVariant extends BaseModel
{
    protected $fillable = [
        'product_id',
        'tenant_id',
        'sku',
        'variant_name',
        'attributes',
        'cost_price',
        'selling_price',
        'barcode',
        'weight',
        'dimensions',
        'status',
    ];

    protected $casts = [
        'attributes' => 'array',
        'dimensions' => 'array',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getProfitMargin(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('sku', 'like', "%{$searchTerm}%")
              ->orWhere('variant_name', 'like', "%{$searchTerm}%")
              ->orWhere('barcode', 'like', "%{$searchTerm}%");
        });
    }
}
