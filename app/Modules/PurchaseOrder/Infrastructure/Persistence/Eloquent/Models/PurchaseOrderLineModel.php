<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderLineModel extends Model {
    protected $table = 'purchase_order_lines';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id','product_id','quantity','unit_cost','total_cost','notes'];
    protected $casts = ['id'=>'int','purchase_order_id'=>'int','product_id'=>'int','quantity'=>'float','unit_cost'=>'float','total_cost'=>'float'];
}
