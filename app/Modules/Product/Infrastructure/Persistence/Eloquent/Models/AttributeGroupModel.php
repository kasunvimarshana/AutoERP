<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AttributeGroupModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attribute_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
