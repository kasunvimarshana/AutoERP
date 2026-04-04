<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductModel extends BaseModel
{
    protected $table = 'products';

    protected $casts = [
        'base_price'      => 'float',
        'cost_price'      => 'float',
        'track_inventory' => 'boolean',
        'track_batch'     => 'boolean',
        'track_serial'    => 'boolean',
        'track_lot'       => 'boolean',
        'attributes'      => 'array',
    ];
}
