<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductVariantModel extends BaseModel
{
    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id', 'product_id', 'sku', 'attributes',
        'price_override', 'cost_override', 'status',
    ];

    protected $casts = [
        'id'             => 'int',
        'tenant_id'      => 'int',
        'product_id'     => 'int',
        'attributes'     => 'array',
        'price_override' => 'float',
        'cost_override'  => 'float',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];
}
