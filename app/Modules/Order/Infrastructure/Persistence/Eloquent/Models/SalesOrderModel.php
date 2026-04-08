<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SalesOrderModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id', 'customer_id', 'warehouse_id', 'reference', 'status',
        'order_date', 'expected_date', 'notes', 'total_amount',
    ];

    protected $casts = [
        'total_amount'  => 'float',
        'order_date'    => 'datetime',
        'expected_date' => 'datetime',
    ];
}
