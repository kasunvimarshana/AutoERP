<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class StockReturnLineModel extends BaseModel
{
    protected $table = 'stock_return_lines';

    protected $casts = [
        'return_qty'         => 'float',
        'unit_price'         => 'float',
        'line_total'         => 'float',
        'quality_checked_at' => 'datetime',
    ];
}
