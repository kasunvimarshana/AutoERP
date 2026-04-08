<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductVariantModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'product_variants';

    protected $fillable = [
        'uuid', 'tenant_id', 'product_id', 'sku', 'barcode', 'name',
        'attribute_values', 'cost_price', 'selling_price', 'weight',
        'is_active', 'images', 'metadata',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'images'           => 'array',
        'metadata'         => 'array',
        'is_active'        => 'boolean',
        'cost_price'       => 'decimal:6',
        'selling_price'    => 'decimal:6',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
