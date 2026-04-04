<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductCategoryModel extends BaseModel
{
    protected $table = 'product_categories';

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
