<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class PurchaseReturnModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'reference',
        'status',
        'return_date',
        'reason',
        'total_amount',
        'credit_memo_number',
        'refund_amount',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'float',
        'refund_amount' => 'float',
    ];
}
