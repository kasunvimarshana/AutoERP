<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class PriceListModel extends BaseModel {
    protected $table = 'price_lists';
    protected $fillable = ['tenant_id','name','currency','discount_percent','is_default','is_active','valid_from','valid_to'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','discount_percent'=>'float','is_default'=>'bool','is_active'=>'bool',
        'valid_from'=>'date','valid_to'=>'date','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
