<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PriceListItemModel extends BaseModel
{
    protected $table = 'price_list_items';

    protected $fillable = [
        'tenant_id',
        'price_list_id',
        'product_id',
        'variation_id',
        'unit_price',
        'min_quantity',
        'max_quantity',
        'discount_percent',
        'markup_percent',
        'currency_code',
        'uom_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'       => 'integer',
        'price_list_id'   => 'integer',
        'product_id'      => 'integer',
        'variation_id'    => 'integer',
        'unit_price'      => 'float',
        'min_quantity'    => 'float',
        'max_quantity'    => 'float',
        'discount_percent'=> 'float',
        'markup_percent'  => 'float',
        'is_active'       => 'boolean',
        'metadata'        => 'array',
    ];
}
