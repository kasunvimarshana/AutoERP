<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryLevelModel extends BaseModel
{
    protected $table = 'inventory_levels';

    protected $casts = [
        'quantity_on_hand'   => 'float',
        'quantity_reserved'  => 'float',
        'quantity_available' => 'float',
        'quantity_on_order'  => 'float',
    ];
}
