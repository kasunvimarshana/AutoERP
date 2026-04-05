<?php
declare(strict_types=1);
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class UnitOfMeasureModel extends BaseModel {
    protected $table = 'units_of_measure';
    protected $fillable = ['tenant_id','category_id','name','symbol','is_base','conversion_factor','type','is_active'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','category_id'=>'int',
        'is_base'=>'bool','conversion_factor'=>'float','is_active'=>'bool',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
