<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PriceListItemModel extends BaseModel
{
    protected $table = 'price_list_items';

    protected $casts = [
        'price'            => 'float',
        'min_qty'          => 'float',
        'max_qty'          => 'float',
        'discount_percent' => 'float',
    ];
}
