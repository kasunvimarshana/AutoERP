<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ComboItemModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'combo_items';

    protected $fillable = [
        'tenant_id',
        'combo_product_id',
        'component_product_id',
        'component_variant_id',
        'quantity',
        'uom_id',
        'metadata',
        'sort_order',
        'is_optional',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'combo_product_id' => 'integer',
        'component_product_id' => 'integer',
        'component_variant_id' => 'integer',
        'uom_id' => 'integer',
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
        'metadata' => 'array',
    ];
}
