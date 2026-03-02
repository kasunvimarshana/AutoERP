<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * ProductImage entity.
 *
 * Represents one of 0..n images attached to a product.
 */
class ProductImage extends Model
{
    use HasTenant;

    protected $table = 'product_images';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
