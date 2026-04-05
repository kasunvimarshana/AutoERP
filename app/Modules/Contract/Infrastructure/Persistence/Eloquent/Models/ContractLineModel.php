<?php
declare(strict_types=1);
namespace Modules\Contract\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class ContractLineModel extends Model {
    public const UPDATED_AT = null;
    protected $table = 'contract_lines';
    protected $fillable = ['contract_id','description','product_id','quantity','unit_price','total_price','due_date','is_delivered','delivered_at'];
    protected $casts = ['id'=>'int','contract_id'=>'int','product_id'=>'int','quantity'=>'float','unit_price'=>'float','total_price'=>'float','is_delivered'=>'bool','due_date'=>'date','delivered_at'=>'datetime','created_at'=>'datetime'];
}
