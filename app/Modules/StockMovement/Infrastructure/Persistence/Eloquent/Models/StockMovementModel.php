<?php
declare(strict_types=1);
namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class StockMovementModel extends BaseModel {
    protected $table = 'stock_movements';
    protected $fillable = ['tenant_id','product_id','warehouse_id','from_location_id','to_location_id','movement_type','quantity','unit_cost','reference','notes','created_by','moved_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','product_id'=>'int','warehouse_id'=>'int','from_location_id'=>'int','to_location_id'=>'int',
        'quantity'=>'float','unit_cost'=>'float','moved_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
