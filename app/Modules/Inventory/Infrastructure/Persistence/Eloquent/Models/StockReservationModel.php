<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservationModel extends Model
{
    protected $table = 'stock_reservations';
    protected $guarded = [];
}
