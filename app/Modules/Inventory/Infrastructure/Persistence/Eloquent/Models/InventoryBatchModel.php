<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryBatchModel extends BaseModel
{
    protected $table = 'inventory_batches';
    protected $fillable = [
        'tenant_id','product_id','warehouse_id','batch_number','lot_number','serial_number',
        'quantity','quantity_remaining','cost_price','manufactured_at','expires_at',
        'received_at','status','reference',
    ];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','product_id'=>'int','warehouse_id'=>'int',
        'quantity'=>'float','quantity_remaining'=>'float','cost_price'=>'float',
        'manufactured_at'=>'datetime','expires_at'=>'datetime','received_at'=>'datetime',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];
}
