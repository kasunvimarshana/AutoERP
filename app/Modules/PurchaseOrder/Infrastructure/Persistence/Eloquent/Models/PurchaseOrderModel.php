<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class PurchaseOrderModel extends BaseModel {
    protected $table = 'purchase_orders';
    protected $fillable = ['tenant_id','supplier_id','warehouse_id','po_number','status','total_amount','currency','expected_date','notes','created_by'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','supplier_id'=>'int','warehouse_id'=>'int','total_amount'=>'float',
        'expected_date'=>'date','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function lines() { return $this->hasMany(PurchaseOrderLineModel::class,'purchase_order_id'); }
}
