<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLineModel extends Model
{
    protected $table = 'purchase_order_lines';
    protected $guarded = [];
}
