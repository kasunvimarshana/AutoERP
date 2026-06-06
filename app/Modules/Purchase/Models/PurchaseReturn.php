<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\PurchaseReturnStatus;

final class PurchaseReturn extends CoreModel
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
        return $this->hasMany(PurchaseReturnLine::class, 'purchase_return_id');
    }

    public function adjustmentAllocations(): HasMany
    {
        return $this->hasMany(PurchaseReturnAdjustmentAllocation::class, 'purchase_return_id');
    }
}
