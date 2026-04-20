<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CostCenterModel extends BaseModel
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'cost_centers';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'description',
        'is_active',
        'path',
        'depth',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'depth' => 'integer',
    ];
}
