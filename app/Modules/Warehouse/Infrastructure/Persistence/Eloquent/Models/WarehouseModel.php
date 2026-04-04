<?php
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class WarehouseModel extends BaseModel
{
    protected $table = 'warehouses';

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
