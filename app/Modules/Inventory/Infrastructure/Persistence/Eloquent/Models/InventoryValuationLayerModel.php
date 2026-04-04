<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryValuationLayerModel extends BaseModel
{
    protected $table = 'inventory_valuation_layers';

    protected $casts = [
        'quantity'           => 'float',
        'remaining_quantity' => 'float',
        'unit_cost'          => 'float',
        'total_cost'         => 'float',
        'receipt_date'       => 'date',
    ];
}
