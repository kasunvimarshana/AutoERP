<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AttributeValueModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'attribute_values';

    protected $fillable = [
        'tenant_id',
        'attribute_id',
        'value',
        'sort_order',
        'label',
        'color_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'attribute_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
