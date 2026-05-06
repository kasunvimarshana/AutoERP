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

class GrnHeaderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'grn_headers';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
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
        'subtotal',
        'tax_total',
        'grand_total',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'supplier_id' => 'integer',
        'warehouse_id' => 'integer',
        'purchase_order_id' => 'integer',
        'currency_id' => 'integer',
        'created_by' => 'integer',
        'exchange_rate' => 'decimal:10',
        'received_date' => 'date',
        'metadata' => 'array',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'grn_header_id');
    }
}
