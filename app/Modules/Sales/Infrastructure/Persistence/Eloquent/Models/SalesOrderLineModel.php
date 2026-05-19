<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLineModel extends Model
{
    protected $table = 'sales_order_lines';
    protected $guarded = [];
}
