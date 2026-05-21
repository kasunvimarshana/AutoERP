<?php

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseModel extends Model
{
    protected $table = 'warehouses';
    protected $guarded = [];

    public function locations()
    {
        return $this->hasMany(WarehouseLocationModel::class, 'warehouse_id', 'id');
    }
}
