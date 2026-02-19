<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model
 *
 * Represents a product in the inventory system.
 * Supports multiple types: goods, services, digital, bundle, composite.
 *
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $category_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string|null $barcode
 * @property string $type
 * @property string $status
 * @property int|null $buy_unit_id
 * @property int|null $sell_unit_id
 * @property float $cost_price
 * @property float $selling_price
 * @property float|null $min_price
 * @property float|null $max_price
 * @property bool $track_inventory
 * @property int $current_stock
 * @property int $reorder_level
 * @property int $reorder_quantity
 * @property int $min_stock_level
 * @property int|null $max_stock_level
 * @property array|null $attributes
 * @property array|null $images
 * @property string|null $manufacturer
 * @property string|null $brand
 * @property string|null $model
 * @property float|null $weight
 * @property string|null $weight_unit
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property string|null $dimension_unit
 * @property bool $is_taxable
 * @property float|null $tax_rate
 * @property bool $allow_discount
 * @property float|null $max_discount_percentage
 * @property string|null $notes
 * @property bool $is_featured
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Product extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Product\Database\Factories\ProductFactory
    {
        return \Modules\Product\Database\Factories\ProductFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'category_id',
        'sku',
        'name',
        'description',
        'barcode',
        'type',
        'status',
        'buy_unit_id',
        'sell_unit_id',
        'cost_price',
        'selling_price',
        'min_price',
        'max_price',
        'track_inventory',
        'current_stock',
        'reorder_level',
        'reorder_quantity',
        'min_stock_level',
        'max_stock_level',
        'attributes',
        'images',
        'manufacturer',
        'brand',
        'model',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'is_taxable',
        'tax_rate',
        'allow_discount',
        'max_discount_percentage',
        'notes',
        'is_featured',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'track_inventory' => 'boolean',
            'current_stock' => 'integer',
            'reorder_level' => 'integer',
            'reorder_quantity' => 'integer',
            'min_stock_level' => 'integer',
            'max_stock_level' => 'integer',
            'attributes' => 'array',
            'images' => 'array',
            'weight' => 'decimal:3',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'is_taxable' => 'boolean',
            'tax_rate' => 'decimal:2',
            'allow_discount' => 'boolean',
            'max_discount_percentage' => 'decimal:2',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the buy unit
     */
    public function buyUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'buy_unit_id');
    }

    /**
     * Get the sell unit
     */
    public function sellUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'sell_unit_id');
    }

    /**
     * Get product variants
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope to filter active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereRaw('current_stock <= reorder_level');
    }

    /**
     * Scope to filter out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
            ->where('current_stock', '<=', 0);
    }

    /**
     * Scope to filter featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Check if product needs reorder
     */
    public function needsReorder(): bool
    {
        return $this->track_inventory && $this->current_stock <= $this->reorder_level;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return ! $this->track_inventory || $this->current_stock > 0;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->track_inventory && $this->current_stock <= 0;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price == 0) {
            return 0;
        }

        return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Get profit amount
     */
    public function getProfitAttribute(): float
    {
        return $this->selling_price - $this->cost_price;
    }

    /**
     * Get stock status as string
     */
    public function getStockStatusAttribute(): string
    {
        if (! $this->track_inventory) {
            return 'not_tracked';
        }

        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        }

        if ($this->current_stock <= $this->reorder_level) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Generate a unique SKU
     */
    public static function generateSKU(string $prefix = 'PRD'): string
    {
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
