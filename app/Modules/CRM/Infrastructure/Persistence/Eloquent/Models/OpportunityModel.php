<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class OpportunityModel extends BaseModel {
    protected $table = 'crm_opportunities';
    protected $fillable = ['tenant_id','name','contact_id','customer_id','owner_id','stage','probability','amount','currency','expected_close_date','description','closed_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','contact_id'=>'int','customer_id'=>'int','owner_id'=>'int','probability'=>'float','amount'=>'float','expected_close_date'=>'datetime','closed_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
