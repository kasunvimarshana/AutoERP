<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryValuationLayerModel extends BaseModel
{
    protected $table = 'inventory_valuation_layers';
    protected $fillable = [
        'tenant_id','product_id','warehouse_id',
        'quantity','quantity_remaining','unit_cost','received_at','reference','batch_id',
    ];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','product_id'=>'int','warehouse_id'=>'int','batch_id'=>'int',
        'quantity'=>'float','quantity_remaining'=>'float','unit_cost'=>'float',
        'received_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];
}
