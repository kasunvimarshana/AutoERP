<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class ServiceOrderModel extends BaseModel {
    protected $table = 'service_orders';
    protected $fillable = ['tenant_id','order_number','type','status','priority','title','description','asset_id','warehouse_id','assigned_to','customer_id','estimated_hours','actual_hours','labor_cost','parts_cost','scheduled_at','started_at','completed_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','asset_id'=>'int','warehouse_id'=>'int','assigned_to'=>'int','customer_id'=>'int','estimated_hours'=>'float','actual_hours'=>'float','labor_cost'=>'float','parts_cost'=>'float','scheduled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
