<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'sku', 'barcode', 'type', 'status',
        'description', 'short_description', 'unit', 'weight', 'weight_unit',
        'has_variants', 'is_trackable', 'is_serial_tracked', 'is_batch_tracked',
        'cost_price', 'sale_price', 'min_stock_level', 'reorder_point',
        'tax_group_id', 'image_url', 'metadata',
    ];

    protected $casts = [
        'has_variants'      => 'boolean',
        'is_trackable'      => 'boolean',
        'is_serial_tracked' => 'boolean',
        'is_batch_tracked'  => 'boolean',
        'cost_price'        => 'float',
        'sale_price'        => 'float',
        'min_stock_level'   => 'float',
        'reorder_point'     => 'float',
        'weight'            => 'float',
        'metadata'          => 'array',
    ];
}
