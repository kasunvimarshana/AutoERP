<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class ShipmentModel extends Model
{
    use HasAudit, HasTenant;

    protected $table = 'shipments';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
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
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
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

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrderModel::class, 'sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }
}
