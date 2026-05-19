<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TransferOrderLineModel extends Model
{
    protected $table = 'transfer_order_lines';
    protected $guarded = [];
}
