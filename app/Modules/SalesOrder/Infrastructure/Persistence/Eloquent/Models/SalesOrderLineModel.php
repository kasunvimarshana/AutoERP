<?php

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SalesOrderLineModel extends BaseModel
{
    protected $table = 'sales_order_lines';

    protected $casts = [
        'ordered_qty'    => 'float',
        'fulfilled_qty'  => 'float',
        'unit_price'     => 'float',
        'line_total'     => 'float',
        'discount_amount' => 'float',
    ];
}
