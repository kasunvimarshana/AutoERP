<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductCategoryModel extends BaseModel
{
    protected $table = 'product_categories';
    protected $fillable = ['tenant_id','parent_id','name','slug','description','is_active','level'];
    protected $casts = [
        'id'=>'int','tenant_id'=>'int','parent_id'=>'int','is_active'=>'bool','level'=>'int',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime',
    ];

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }
}
