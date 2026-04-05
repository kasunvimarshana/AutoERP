<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryCycleCountModel extends BaseModel
{
    protected $table = 'inventory_cycle_counts';
    protected $fillable = [
        'tenant_id','warehouse_id','product_id','status','counted_by',
        'started_at','completed_at','notes',
    ];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','warehouse_id'=>'int','product_id'=>'int','counted_by'=>'int',
        'started_at'=>'datetime','completed_at'=>'datetime',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];
}
