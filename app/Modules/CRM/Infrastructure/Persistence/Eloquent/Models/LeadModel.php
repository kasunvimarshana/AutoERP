<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class LeadModel extends BaseModel {
    protected $table = 'crm_leads';
    protected $fillable = ['tenant_id','name','email','phone','company','source','status','estimated_value','owner_id','notes','converted_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','owner_id'=>'int','estimated_value'=>'float','converted_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
