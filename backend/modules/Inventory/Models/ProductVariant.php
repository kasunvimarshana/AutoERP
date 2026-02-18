<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

/**
 * Product Variant Model
 *
 * Represents a variant of a product (e.g., different sizes, colors).
 */
class ProductVariant extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku',
        'variant_attributes',
        'price_adjustment',
        'cost_adjustment',
        'barcode',
        'status',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'price_adjustment' => 'decimal:4',
        'cost_adjustment' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
