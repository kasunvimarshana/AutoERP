<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    protected $guarded = [];
}
