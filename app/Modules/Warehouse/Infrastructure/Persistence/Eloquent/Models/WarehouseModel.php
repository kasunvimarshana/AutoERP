<?php

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseModel extends Model
{
    protected $table = 'warehouses';
    protected $guarded = [];
}
