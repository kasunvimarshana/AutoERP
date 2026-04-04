<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UomConversionModel extends BaseModel
{
    protected $table = 'uom_conversions';

    protected $casts = [
        'factor' => 'float',
    ];
}
