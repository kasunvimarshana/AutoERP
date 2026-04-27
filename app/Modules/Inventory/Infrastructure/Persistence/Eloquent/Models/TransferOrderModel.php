<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TransferOrderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'transfer_orders';

    protected $fillable = [
        'tenant_id',
            'org_unit_id',
            'row_version',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_number',
        'status',
        'request_date',
        'expected_date',
        'shipped_date',
        'received_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'from_warehouse_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'request_date' => 'date',
        'expected_date' => 'date',
        'shipped_date' => 'date',
        'received_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(TransferOrderLineModel::class, 'transfer_order_id');
    }
}
