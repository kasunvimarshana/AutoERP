<?php
declare(strict_types=1);
namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class WarehouseLocationModel extends BaseModel {
    protected $table = 'warehouse_locations';
    protected $fillable = ['tenant_id','warehouse_id','parent_id','name','code','type','level','is_active'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','warehouse_id'=>'int','parent_id'=>'int','level'=>'int','is_active'=>'bool',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function children() { return $this->hasMany(static::class,'parent_id'); }
}
