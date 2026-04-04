<?php
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PurchaseOrderModel extends BaseModel
{
    protected $table = 'purchase_orders';

    protected $casts = [
        'total_amount'           => 'float',
        'tax_amount'             => 'float',
        'expected_delivery_date' => 'date',
        'approved_at'            => 'datetime',
    ];
}
