<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AttributeModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attributes';

    protected $fillable = [
        'tenant_id',
        'group_id',
        'name',
        'type',
        'is_required',
        'code',
        'description',
        'sort_order',
        'is_active',
        'is_filterable',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'group_id' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_filterable' => 'boolean',
        'metadata' => 'array',
    ];
}
