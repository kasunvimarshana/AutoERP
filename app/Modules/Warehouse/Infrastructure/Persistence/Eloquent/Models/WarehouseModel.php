<?php
declare(strict_types=1);
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class WarehouseModel extends BaseModel {
    protected $table = 'warehouses';
    protected $fillable = ['tenant_id','name','code','type','address','is_active','manager_id','metadata'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','manager_id'=>'int','is_active'=>'bool','metadata'=>'array',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
