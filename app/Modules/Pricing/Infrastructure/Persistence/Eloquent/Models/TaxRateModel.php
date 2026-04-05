<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class TaxRateModel extends BaseModel {
    protected $table = 'tax_rates';
    protected $fillable = ['tenant_id','name','code','rate','type','is_compound','is_active','applies_to'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','rate'=>'float','is_compound'=>'bool','is_active'=>'bool',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
