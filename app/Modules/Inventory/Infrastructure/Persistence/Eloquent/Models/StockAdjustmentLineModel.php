<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLineModel extends Model
{
    protected $table = 'stock_adjustment_lines';
    protected $guarded = [];
}
