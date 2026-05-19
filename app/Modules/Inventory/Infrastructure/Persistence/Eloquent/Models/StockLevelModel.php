<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevelModel extends Model
{
    protected $table = 'stock_levels';
    protected $guarded = [];
}
