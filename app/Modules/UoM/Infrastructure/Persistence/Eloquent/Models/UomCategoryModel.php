<?php
declare(strict_types=1);
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class UomCategoryModel extends BaseModel {
    protected $table = 'uom_categories';
    protected $fillable = ['tenant_id','name','type','is_active'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','is_active'=>'bool',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
