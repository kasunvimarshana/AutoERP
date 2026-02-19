<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable;
use Modules\Product\Enums\ProductType;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * Product Model
 *
 * Flexible product model supporting goods, services, bundles, and composites
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property ProductType $type
 * @property string|null $description
 * @property string|null $category_id
 * @property string|null $buying_unit_id
 * @property string|null $selling_unit_id
 * @property array $metadata
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Product extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'description',
        'category_id',
        'buying_unit_id',
        'selling_unit_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the buying unit
     */
    public function buyingUnit()
    {
        return $this->belongsTo(Unit::class, 'buying_unit_id');
    }

    /**
     * Get the selling unit
     */
    public function sellingUnit()
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }

    /**
     * Get product prices
     */
    public function prices()
    {
        return $this->hasMany(\Modules\Pricing\Models\ProductPrice::class);
    }

    /**
     * Get bundle items (if this is a bundle)
     */
    public function bundleItems()
    {
        return $this->hasMany(ProductBundle::class, 'bundle_id');
    }

    /**
     * Get composite parts (if this is a composite)
     */
    public function compositeParts()
    {
        return $this->hasMany(ProductComposite::class, 'composite_id')->orderBy('sort_order');
    }

    /**
     * Get composite components with relationship
     */
    public function components()
    {
        return $this->belongsToMany(Product::class, 'product_composites', 'composite_id', 'component_id')
            ->withPivot(['quantity', 'sort_order'])
            ->withTimestamps()
            ->orderBy('product_composites.sort_order');
    }

    /**
     * Get unit conversions
     */
    public function unitConversions()
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    /**
     * Scope active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, ProductType $type)
    {
        return $query->where('type', $type);
    }
}
