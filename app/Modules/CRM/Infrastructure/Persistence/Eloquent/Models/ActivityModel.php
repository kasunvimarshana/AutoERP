<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class ActivityModel extends BaseModel {
    protected $table = 'crm_activities';
    protected $fillable = ['tenant_id','type','subject','description','status','owner_id','contact_id','lead_id','opportunity_id','scheduled_at','completed_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','owner_id'=>'int','contact_id'=>'int','lead_id'=>'int','opportunity_id'=>'int','scheduled_at'=>'datetime','completed_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
