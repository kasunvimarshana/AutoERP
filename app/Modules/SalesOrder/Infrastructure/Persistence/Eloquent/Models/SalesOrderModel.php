<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class SalesOrderModel extends BaseModel {
    protected $table = 'sales_orders';
    protected $fillable = ['tenant_id','customer_id','warehouse_id','so_number','status','subtotal','tax_amount','total_amount','currency','notes','created_by'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','customer_id'=>'int','warehouse_id'=>'int',
        'subtotal'=>'float','tax_amount'=>'float','total_amount'=>'float',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function lines() { return $this->hasMany(SalesOrderLineModel::class,'sales_order_id'); }
}
