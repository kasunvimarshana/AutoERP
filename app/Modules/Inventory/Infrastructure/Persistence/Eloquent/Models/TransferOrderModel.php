<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TransferOrderModel extends Model
{
    protected $table = 'transfer_orders';
    protected $guarded = [];
}
