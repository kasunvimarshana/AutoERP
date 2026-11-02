<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\Concerns\HasPurchaseRowVersion;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\UserModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class PurchaseOrder extends TenantOwnedModel
{
    use HasPurchaseRowVersion;
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'supplier_id' => 'integer',
            'purchase_order_date' => 'date',
            'expected_delivery_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'charge_total' => 'decimal:6',
            'adjustment_total' => 'decimal:6',
            'header_increase_total' => 'decimal:6',
            'header_decrease_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'received_quantity' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'returned_quantity' => 'decimal:6',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ]);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'closed_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PurchaseHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'purchase_order');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'purchase_order_id');
    }
}
