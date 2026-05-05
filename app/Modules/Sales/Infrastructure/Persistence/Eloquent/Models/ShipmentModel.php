<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ShipmentModel extends Model
{
    use HasAudit, HasTenant;

    protected $table = 'shipments';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sales_order_id',
        'warehouse_id',
        'shipment_number',
        'status',
        'shipped_date',
        'carrier',
        'tracking_number',
        'currency_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'sales_order_id' => 'integer',
        'warehouse_id' => 'integer',
        'currency_id' => 'integer',
        'shipped_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ShipmentLineModel::class, 'shipment_id');
    }
}
