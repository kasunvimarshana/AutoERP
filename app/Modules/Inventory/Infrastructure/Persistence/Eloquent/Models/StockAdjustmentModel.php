<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentModel extends Model
{
    protected $table = 'stock_adjustments';
    protected $guarded = [];
}
