<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * ProductVariant entity.
 *
 * Represents a variant of a parent product (e.g. size/colour combinations).
 */
class ProductVariant extends Model
{
    use HasTenant;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_name',
        'sku',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active'  => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
