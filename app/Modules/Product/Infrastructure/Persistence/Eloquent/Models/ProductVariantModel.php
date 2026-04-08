<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductVariantModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id', 'product_id', 'name', 'sku', 'barcode',
        'attributes', 'cost_price', 'sale_price', 'stock_quantity', 'is_active',
    ];

    protected $casts = [
        'attributes'     => 'array',
        'cost_price'     => 'float',
        'sale_price'     => 'float',
        'stock_quantity' => 'float',
        'is_active'      => 'boolean',
    ];
}
