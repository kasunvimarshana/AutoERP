<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class ContactModel extends BaseModel {
    protected $table = 'crm_contacts';
    protected $fillable = ['tenant_id','type','first_name','last_name','company','job_title','email','phone','mobile','address','owner_id','customer_id','supplier_id','is_active'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','owner_id'=>'int','customer_id'=>'int','supplier_id'=>'int','is_active'=>'bool','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
