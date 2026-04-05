<?php
declare(strict_types=1);
namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class DispatchModel extends BaseModel {
    protected $table = 'dispatches';
    protected $fillable = ['tenant_id','sales_order_id','warehouse_id','dispatch_number','status','carrier','tracking_number','shipping_cost','shipped_at','delivered_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','sales_order_id'=>'int','warehouse_id'=>'int',
        'shipping_cost'=>'float','shipped_at'=>'datetime','delivered_at'=>'datetime',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function lines() { return $this->hasMany(DispatchLineModel::class,'dispatch_id'); }
}
