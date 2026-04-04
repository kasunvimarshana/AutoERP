<?php

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SalesOrderModel extends BaseModel
{
    protected $table = 'sales_orders';

    protected $casts = [
        'total_amount'           => 'float',
        'tax_amount'             => 'float',
        'discount_amount'        => 'float',
        'expected_delivery_date' => 'date',
        'picked_at'              => 'datetime',
        'packed_at'              => 'datetime',
    ];
}
