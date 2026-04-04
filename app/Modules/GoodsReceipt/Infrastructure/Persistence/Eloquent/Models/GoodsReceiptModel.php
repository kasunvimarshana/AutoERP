<?php
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class GoodsReceiptModel extends BaseModel
{
    protected $table = 'goods_receipts';

    protected $casts = [
        'received_at'  => 'datetime',
        'inspected_at' => 'datetime',
        'put_away_at'  => 'datetime',
    ];
}
