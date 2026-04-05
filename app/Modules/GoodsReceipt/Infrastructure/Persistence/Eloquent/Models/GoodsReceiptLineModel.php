<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class GoodsReceiptLineModel extends Model {
    protected $table = 'goods_receipt_lines';
    public $timestamps = false;
    protected $fillable = ['goods_receipt_id','product_id','quantity_ordered','quantity_received','unit_cost','batch_number','lot_number','serial_number','notes'];
    protected $casts = ['id'=>'int','goods_receipt_id'=>'int','product_id'=>'int','quantity_ordered'=>'float','quantity_received'=>'float','unit_cost'=>'float'];
}
