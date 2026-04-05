<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class AccountModel extends BaseModel {
    protected $table = 'accounts';
    protected $fillable = ['tenant_id','code','name','type','subtype','parent_id','balance','currency','is_active','description'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','parent_id'=>'int','balance'=>'float',
        'is_active'=>'bool','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
