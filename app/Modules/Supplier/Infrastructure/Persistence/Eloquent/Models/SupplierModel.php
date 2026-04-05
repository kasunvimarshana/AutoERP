<?php
declare(strict_types=1);
namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class SupplierModel extends BaseModel {
    protected $table = 'suppliers';
    protected $fillable = ['tenant_id','name','code','email','phone','address','is_active','metadata'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','is_active'=>'bool','metadata'=>'array',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
