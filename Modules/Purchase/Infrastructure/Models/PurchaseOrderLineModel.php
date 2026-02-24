<?php
namespace Modules\Purchase\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderLineModel extends Model
{
    use HasUuids;
    protected $table = 'purchase_order_lines';
    protected $fillable = [
        'id', 'purchase_order_id', 'product_id', 'description',
        'qty', 'received_qty', 'unit_price', 'tax_rate', 'tax_amount',
        'line_total', 'uom', 'sort_order',
    ];
    public $timestamps = false;
}
