<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SalesOrderModel extends Model
{
    use HasAudit, HasTenant;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'customer_id',
            'org_unit_id',
            'row_version',
        'warehouse_id',
        'so_number',
        'status',
        'currency_id',
        'exchange_rate',
        'order_date',
        'requested_delivery_date',
        'price_list_id',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'org_unit_id' => 'integer',
        'warehouse_id' => 'integer',
        'currency_id' => 'integer',
        'price_list_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'order_date' => 'date',
        'requested_delivery_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'sales_order_id');
    }
}
