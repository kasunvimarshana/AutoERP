<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SalesReturnModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'sales_returns';

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'reference',
        'status',
        'return_date',
        'reason',
        'total_amount',
        'credit_memo_number',
        'refund_amount',
        'restocking_fee',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'float',
        'refund_amount' => 'float',
        'restocking_fee' => 'float',
    ];
}
