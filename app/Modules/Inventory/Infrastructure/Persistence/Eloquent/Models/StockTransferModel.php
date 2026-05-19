<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferModel extends Model
{
    protected $table = 'stock_transfers';
    protected $guarded = [];
}
