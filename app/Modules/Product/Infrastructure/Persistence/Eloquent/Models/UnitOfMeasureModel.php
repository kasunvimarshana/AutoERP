<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class UnitOfMeasureModel extends BaseModel
{
    use HasTenant;

    use HasAudit;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'type',
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];
}
