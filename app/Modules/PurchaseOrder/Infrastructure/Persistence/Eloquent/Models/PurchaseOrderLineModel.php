<?php
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PurchaseOrderLineModel extends BaseModel
{
    protected $table = 'purchase_order_lines';

    protected $casts = [
        'ordered_qty'  => 'float',
        'received_qty' => 'float',
        'unit_cost'    => 'float',
        'line_total'   => 'float',
    ];
}
