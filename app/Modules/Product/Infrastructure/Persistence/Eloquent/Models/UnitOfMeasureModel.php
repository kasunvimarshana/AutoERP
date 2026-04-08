<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class UnitOfMeasureModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'is_base_unit',
        'conversion_factor',
        'metadata',
    ];

    protected $casts = [
        'is_base_unit'      => 'boolean',
        'conversion_factor' => 'decimal:6',
        'metadata'          => 'array',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];
}
