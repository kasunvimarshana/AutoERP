<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class SalesOrderLineModel extends Model {
    protected $table = 'sales_order_lines';
    public $timestamps = false;
    protected $fillable = ['sales_order_id','product_id','quantity','unit_price','tax_rate','discount_percent','line_total','notes'];
    protected $casts = ['id'=>'int','sales_order_id'=>'int','product_id'=>'int','quantity'=>'float','unit_price'=>'float','tax_rate'=>'float','discount_percent'=>'float','line_total'=>'float'];
}
