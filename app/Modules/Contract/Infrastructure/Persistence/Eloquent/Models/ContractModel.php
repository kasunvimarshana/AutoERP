<?php
declare(strict_types=1);
namespace Modules\Contract\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class ContractModel extends BaseModel {
    protected $table = 'contracts';
    protected $fillable = ['tenant_id','contract_number','type','status','title','description','customer_id','supplier_id','owner_id','value','currency','start_date','end_date','terms','auto_renew','terminated_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','customer_id'=>'int','supplier_id'=>'int','owner_id'=>'int','value'=>'float','auto_renew'=>'bool','start_date'=>'date','end_date'=>'date','terminated_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
