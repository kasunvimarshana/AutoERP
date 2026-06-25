<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Supplier\Models\Supplier;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class PurchaseReturn extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'purchase_returns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'return_date' => 'date',
            'status' => PurchaseReturnStatus::class,
            'return_type' => PurchaseReturnType::class,
            'source_id' => 'integer',
            'approval_required' => 'boolean',
            'affects_supplier_balance' => 'boolean',
            'cost_basis' => 'decimal:6',
            'audit_metadata' => 'array',
            'subtotal' => 'decimal:6',
            'adjustment_return_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'debit_note_id' => 'integer',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ]);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class, 'purchase_return_id')
            ->orderBy('line_number')
            ->orderBy('id');
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

    public function adjustmentAllocations(): HasMany
    {
        return $this->hasMany(PurchaseReturnAdjustmentAllocation::class, 'purchase_return_id');
    }

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(PurchaseDebitNote::class, 'debit_note_id');
    }

    public function sourceGoodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'source_id');
    }
}
