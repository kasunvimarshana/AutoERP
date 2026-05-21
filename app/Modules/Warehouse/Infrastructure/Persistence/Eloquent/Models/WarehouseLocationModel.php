<?php

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseLocationModel extends Model
{
    protected $table = 'warehouse_locations';
    protected $guarded = [];

    public function warehouse()
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id', 'id');
    }
}
