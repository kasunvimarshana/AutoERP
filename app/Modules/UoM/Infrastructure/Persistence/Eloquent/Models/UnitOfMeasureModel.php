<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UnitOfMeasureModel extends BaseModel
{
    protected $table = 'units_of_measure';

    protected $casts = [
        'conversion_factor' => 'float',
        'is_base'           => 'boolean',
        'is_active'         => 'boolean',
    ];
}
