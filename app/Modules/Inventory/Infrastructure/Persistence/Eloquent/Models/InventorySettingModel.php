<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventorySettingModel extends BaseModel
{
    protected $table = 'inventory_settings';

    protected $casts = [
        'negative_stock_allowed' => 'boolean',
        'auto_reorder_enabled'   => 'boolean',
        'default_reorder_point'  => 'float',
        'default_reorder_qty'    => 'float',
    ];
}
