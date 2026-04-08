<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UnitOfMeasureModel extends BaseModel
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'uom_category_id',
        'name',
        'code',
        'symbol',
        'is_base_unit',
        'factor',
        'description',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'      => 'integer',
        'uom_category_id' => 'integer',
        'is_base_unit'   => 'boolean',
        'factor'         => 'float',
        'is_active'      => 'boolean',
    ];
}
