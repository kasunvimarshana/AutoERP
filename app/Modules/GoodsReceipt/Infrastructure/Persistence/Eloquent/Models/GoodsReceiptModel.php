<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class GoodsReceiptModel extends BaseModel {
    protected $table = 'goods_receipts';
    protected $fillable = ['tenant_id','purchase_order_id','warehouse_id','gr_number','status','notes',
        'received_by','inspected_by','inspected_at','put_away_by','put_away_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','purchase_order_id'=>'int','warehouse_id'=>'int',
        'received_by'=>'int','inspected_by'=>'int','put_away_by'=>'int',
        'inspected_at'=>'datetime','put_away_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function lines() { return $this->hasMany(GoodsReceiptLineModel::class,'goods_receipt_id'); }
}
