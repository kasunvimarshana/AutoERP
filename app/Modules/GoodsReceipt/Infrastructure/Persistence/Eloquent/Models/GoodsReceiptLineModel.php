<?php
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class GoodsReceiptLineModel extends BaseModel
{
    protected $table = 'goods_receipt_lines';

    protected $casts = [
        'expected_qty' => 'float',
        'received_qty' => 'float',
        'unit_cost'    => 'float',
    ];
}
