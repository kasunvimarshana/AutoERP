<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class MaintenanceScheduleModel extends BaseModel {
    protected $table = 'maintenance_schedules';
    protected $fillable = ['tenant_id','name','asset_id','maintenance_type','frequency_value','frequency_unit','last_run_at','next_run_at','is_active'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','asset_id'=>'int','frequency_value'=>'int','is_active'=>'bool','last_run_at'=>'datetime','next_run_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
