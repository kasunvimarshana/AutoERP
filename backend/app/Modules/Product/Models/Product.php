<?php

namespace App\Modules\Product\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="sku", type="string", example="PROD-001"),
 *     @OA\Property(property="name", type="string", example="Laptop Computer"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="category", type="string", example="Electronics"),
 *     @OA\Property(property="unit_of_measure", type="string", example="pcs"),
 *     @OA\Property(property="is_variant_product", type="boolean", example=false),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="type", type="string", enum={"product", "service"}, example="product"),
 *     @OA\Property(property="tax_rate", type="number", format="float", example=0.15),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Product extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'category',
        'unit_of_measure',
        'is_variant_product',
        'status',
        'type',
        'tax_rate',
        'settings',
    ];

    protected $casts = [
        'is_variant_product' => 'boolean',
        'tax_rate' => 'decimal:4',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Categories relationship (optional - implement when Category model is created)
     * 
     * Uncomment when Category model and product_categories pivot table are created:
     * 
     * public function categories(): BelongsToMany
     * {
     *     return $this->belongsToMany(\App\Models\Category::class, 'product_categories', 'product_id', 'category_id');
     * }
     */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVariantProduct(): bool
    {
        return $this->is_variant_product === true;
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isProduct(): bool
    {
        return $this->type === 'product';
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

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('sku', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%");
        });
    }
}
