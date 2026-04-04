<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class StockReturnModel extends BaseModel
{
    protected $table = 'stock_returns';

    protected $casts = [
        'total_amount'   => 'float',
        'restocking_fee' => 'float',
        'approved_at'    => 'datetime',
        'completed_at'   => 'datetime',
    ];
}
