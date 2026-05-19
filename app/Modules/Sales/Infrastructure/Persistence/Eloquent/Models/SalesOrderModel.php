<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderModel extends Model
{
    protected $table = 'sales_orders';
    protected $guarded = [];
}
