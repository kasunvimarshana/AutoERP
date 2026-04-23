<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SerialModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'serials';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_number',
        'status',
        'sold_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'sold_at' => 'datetime',
        'metadata' => 'array',
    ];
}
