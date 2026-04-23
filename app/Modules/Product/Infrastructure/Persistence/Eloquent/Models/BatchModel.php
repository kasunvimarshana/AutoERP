<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BatchModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'batches';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'quantity',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'string',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];
}
