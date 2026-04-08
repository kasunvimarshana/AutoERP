<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UomCategoryModel extends BaseModel
{
    protected $table = 'uom_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
    ];
}
