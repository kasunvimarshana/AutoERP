<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UomCategoryModel extends BaseModel
{
    protected $table = 'uom_categories';

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
