<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class PurchaseOrderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'org_unit_id',
        'row_version',
        'warehouse_id',
        'po_number',
        'status',
        'currency_id',
        'exchange_rate',
        'order_date',
        'expected_date',
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
        'supplier_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'warehouse_id' => 'integer',
        'status' => 'string',
        'currency_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'order_date' => 'date',
        'expected_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
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
