<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BatchModel extends Model
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
        'received_date',
        'supplier_id',
        'status',
        'notes',
        'metadata',
        'sales_price',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'supplier_id' => 'integer',
        'metadata'   => 'array',
    ];
}
