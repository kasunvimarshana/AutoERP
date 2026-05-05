<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class GrnHeaderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'grn_headers';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'warehouse_id',
        'purchase_order_id',
        'grn_number',
        'status',
        'received_date',
        'currency_id',
        'exchange_rate',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'supplier_id' => 'integer',
        'warehouse_id' => 'integer',
        'purchase_order_id' => 'integer',
        'currency_id' => 'integer',
        'created_by' => 'integer',
        'exchange_rate' => 'decimal:10',
        'received_date' => 'date',
        'metadata' => 'array',
    ];
}
