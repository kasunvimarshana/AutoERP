<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryLevelModel extends BaseModel
{
    protected $table = 'inventory_levels';
    protected $fillable = [
        'tenant_id','product_id','warehouse_id','location_id',
        'quantity_on_hand','quantity_reserved','quantity_in_transit','valuation_method',
    ];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','product_id'=>'int','warehouse_id'=>'int','location_id'=>'int',
        'quantity_on_hand'=>'float','quantity_reserved'=>'float','quantity_in_transit'=>'float',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];
}
