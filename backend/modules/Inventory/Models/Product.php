<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\Inventory\Enums\CostMethod;
use Modules\Inventory\Enums\ProductStatus;
use Modules\Inventory\Enums\ProductType;

/**
 * Product Model
 *
 * Represents a product in the inventory system.
 * Supports multiple product types: inventory, service, bundle, and composite.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property ProductType $product_type
 * @property string|null $category_id
 * @property string|null $base_uom_id
 * @property bool $track_inventory
 * @property bool $track_batches
 * @property bool $track_serials
 * @property bool $has_expiry
 * @property float|null $reorder_level
 * @property float|null $reorder_quantity
 * @property CostMethod $cost_method
 * @property float|null $standard_cost
 * @property float|null $last_purchase_cost
 * @property float|null $average_cost
 * @property float|null $selling_price
 * @property ProductStatus $status
 * @property array|null $custom_attributes
 * @property string|null $barcode
 * @property string|null $manufacturer
 * @property string|null $brand
 * @property float|null $weight
 * @property string|null $weight_uom
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property string|null $dimension_uom
 * @property string|null $image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Product extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'product_type',
        'category_id',
        'base_uom_id',
        'track_inventory',
        'track_batches',
        'track_serials',
        'has_expiry',
        'reorder_level',
        'reorder_quantity',
        'cost_method',
        'standard_cost',
        'last_purchase_cost',
        'average_cost',
        'selling_price',
        'status',
        'custom_attributes',
        'barcode',
        'manufacturer',
        'brand',
        'weight',
        'weight_uom',
        'length',
        'width',
        'height',
        'dimension_uom',
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'product_type' => ProductType::class,
        'cost_method' => CostMethod::class,
        'status' => ProductStatus::class,
        'track_inventory' => 'boolean',
        'track_batches' => 'boolean',
        'track_serials' => 'boolean',
        'has_expiry' => 'boolean',
        'reorder_level' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'standard_cost' => 'decimal:4',
        'last_purchase_cost' => 'decimal:4',
        'average_cost' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'custom_attributes' => 'array',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the product variants.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Get the product attributes.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    /**
     * Get the stock ledger entries for this product.
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'product_id');
    }

    /**
     * Get the current stock levels for this product.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'product_id');
    }

    /**
     * Get the pricing rules for this product.
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'product_id');
    }

    /**
     * Scope to filter active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    /**
     * Scope to filter by product type.
     */
    public function scopeOfType($query, ProductType $type)
    {
        return $query->where('product_type', $type);
    }

    /**
     * Scope to filter products that track inventory.
     */
    public function scopeTracksInventory($query)
    {
        return $query->where('track_inventory', true);
    }

    /**
     * Check if the product is active.
     */
    public function isActive(): bool
    {
        return $this->status === ProductStatus::ACTIVE;
    }

    /**
     * Check if the product tracks inventory.
     */
    public function tracksInventory(): bool
    {
        return $this->track_inventory;
    }

    /**
     * Check if the product tracks batches.
     */
    public function tracksBatches(): bool
    {
        return $this->track_batches;
    }

    /**
     * Check if the product tracks serial numbers.
     */
    public function tracksSerials(): bool
    {
        return $this->track_serials;
    }

    /**
     * Check if the product has expiry.
     */
    public function hasExpiry(): bool
    {
        return $this->has_expiry;
    }

    /**
     * Get the profit margin percentage.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->selling_price || ! $this->average_cost) {
            return null;
        }

        return (($this->selling_price - $this->average_cost) / $this->selling_price) * 100;
    }

    /**
     * Get the markup percentage.
     */
    public function getMarkupAttribute(): ?float
    {
        if (! $this->selling_price || ! $this->average_cost) {
            return null;
        }

        return (($this->selling_price - $this->average_cost) / $this->average_cost) * 100;
    }
}
