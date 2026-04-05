<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceOrderLineModel extends Model {
    public const UPDATED_AT = null;
    protected $table = 'service_order_lines';
    protected $fillable = ['service_order_id','description','product_id','quantity','unit_cost','total_cost'];
    protected $casts = ['id'=>'int','service_order_id'=>'int','product_id'=>'int','quantity'=>'float','unit_cost'=>'float','total_cost'=>'float','created_at'=>'datetime'];
}
