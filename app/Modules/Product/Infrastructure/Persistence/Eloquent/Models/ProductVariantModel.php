<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductVariantModel extends BaseModel
{
    protected $table = 'product_variants';

    protected $casts = [
        'base_price' => 'float',
        'cost_price' => 'float',
        'attributes' => 'array',
        'is_active'  => 'boolean',
    ];
}
