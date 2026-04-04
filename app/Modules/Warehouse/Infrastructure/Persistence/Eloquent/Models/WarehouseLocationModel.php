<?php
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class WarehouseLocationModel extends BaseModel
{
    protected $table = 'warehouse_locations';

    protected $casts = [
        'is_active'  => 'boolean',
        'max_weight' => 'float',
        'max_volume' => 'float',
    ];
}
