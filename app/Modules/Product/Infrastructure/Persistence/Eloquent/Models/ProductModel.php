<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductModel extends BaseModel
{
    protected $table = 'products';
    protected $fillable = [
        'tenant_id','category_id','name','slug','sku','type','description','status',
        'base_price','tax_rate','weight','unit','is_trackable','is_serialized',
        'is_batch_tracked','min_stock_level','reorder_point','metadata',
    ];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','category_id'=>'int',
        'base_price'=>'float','tax_rate'=>'float','weight'=>'float',
        'is_trackable'=>'bool','is_serialized'=>'bool','is_batch_tracked'=>'bool',
        'min_stock_level'=>'float','reorder_point'=>'float','metadata'=>'array',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];
}
