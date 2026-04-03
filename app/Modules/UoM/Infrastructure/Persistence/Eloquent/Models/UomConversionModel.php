<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UomConversionModel extends BaseModel
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'tenant_id',
        'from_uom_id',
        'to_uom_id',
        'factor',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'from_uom_id' => 'integer',
        'to_uom_id'   => 'integer',
        'factor'     => 'float',
        'is_active'  => 'boolean',
    ];
}
