<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Variant Model
 *
 * Represents variations of a product (e.g., different sizes, colors).
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $branch_id
 * @property string $sku
 * @property string $name
 * @property string|null $barcode
 * @property array|null $variant_attributes
 * @property float|null $cost_price
 * @property float|null $selling_price
 * @property int $current_stock
 * @property int|null $reorder_level
 * @property int|null $reorder_quantity
 * @property array|null $images
 * @property float|null $weight
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ProductVariant extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'branch_id',
        'sku',
        'name',
        'barcode',
        'variant_attributes',
        'cost_price',
        'selling_price',
        'current_stock',
        'reorder_level',
        'reorder_quantity',
        'images',
        'weight',
        'is_default',
        'is_active',
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
            'variant_attributes' => 'array',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'current_stock' => 'integer',
            'reorder_level' => 'integer',
            'reorder_quantity' => 'integer',
            'images' => 'array',
            'weight' => 'decimal:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the parent product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to filter active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default variant
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter low stock variants
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= reorder_level');
    }

    /**
     * Get effective cost price (variant or product)
     */
    public function getEffectiveCostPriceAttribute(): float
    {
        return $this->cost_price ?? $this->product->cost_price;
    }

    /**
     * Get effective selling price (variant or product)
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        return $this->selling_price ?? $this->product->selling_price;
    }

    /**
     * Check if variant needs reorder
     */
    public function needsReorder(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->current_stock > 0;
    }
}
