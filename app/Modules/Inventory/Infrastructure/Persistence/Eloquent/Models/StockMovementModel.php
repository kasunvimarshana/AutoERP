<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovementModel extends Model
{
    protected $table = 'stock_movements';
    protected $guarded = [];
}
