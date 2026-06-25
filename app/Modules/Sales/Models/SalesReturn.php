<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Enums\SalesReturnType;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class SalesReturn extends CoreModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'return_date' => 'date',
            'return_type' => SalesReturnType::class,
            'status' => SalesReturnStatus::class,
            'subtotal' => 'decimal:6',
            'adjustment_return_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'affects_inventory' => 'boolean',
            'affects_customer_balance' => 'boolean',
            'approval_required' => 'boolean',
            'cost_basis' => 'decimal:6',
            'audit_metadata' => 'array',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class);
    }

    public function replacementSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(SalesCreditNote::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class);
    }

    public function adjustmentAllocations(): HasMany
    {
        return $this->hasMany(SalesReturnAdjustmentAllocation::class);
    }
}
