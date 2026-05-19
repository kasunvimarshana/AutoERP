<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferLineModel extends Model
{
    protected $table = 'stock_transfer_lines';
    protected $guarded = [];
}
