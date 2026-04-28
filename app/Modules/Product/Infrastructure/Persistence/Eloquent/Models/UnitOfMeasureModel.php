<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class UnitOfMeasureModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'name',
        'symbol',
        'type',
        'is_base',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'type' => 'string',
        'is_base' => 'boolean',
    ];
}
