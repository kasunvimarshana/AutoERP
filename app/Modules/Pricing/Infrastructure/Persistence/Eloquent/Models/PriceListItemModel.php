<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PriceListItemModel extends BaseModel
{
    protected $table = 'price_list_items';

    protected $fillable = [
        'tenant_id', 'price_list_id', 'product_id', 'variant_id',
        'price_type', 'value', 'min_quantity', 'currency',
    ];

    protected $casts = [
        'id'            => 'int',
        'tenant_id'     => 'int',
        'price_list_id' => 'int',
        'product_id'    => 'int',
        'variant_id'    => 'int',
        'value'         => 'float',
        'min_quantity'  => 'float',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];
}
