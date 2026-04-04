<?php
namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class StockMovementModel extends BaseModel
{
    protected $table = 'stock_movements';

    protected $casts = [
        'quantity'  => 'float',
        'unit_cost' => 'float',
        'moved_at'  => 'datetime',
    ];
}
