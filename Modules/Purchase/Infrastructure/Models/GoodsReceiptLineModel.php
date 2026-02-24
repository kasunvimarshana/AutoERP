<?php
namespace Modules\Purchase\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class GoodsReceiptLineModel extends Model
{
    use HasUuids;
    protected $table = 'purchase_goods_receipt_lines';
    protected $fillable = [
        'id', 'goods_receipt_id', 'purchase_order_line_id', 'product_id',
        'qty_received', 'qty_accepted', 'qty_rejected', 'rejection_reason',
        'lot_number', 'expiry_date', 'location_id',
    ];
    protected $casts = ['expiry_date' => 'date'];
    public $timestamps = false;
}
