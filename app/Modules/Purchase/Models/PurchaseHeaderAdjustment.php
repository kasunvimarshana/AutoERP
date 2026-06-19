<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final class PurchaseHeaderAdjustment extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_header_adjustments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'source_id' => 'integer',
            'origin_purchase_header_adjustment_id' => 'integer',
            'adjustment_type' => PurchaseAdjustmentType::class,
            'effect' => PurchaseAdjustmentEffect::class,
            'calculation_type' => PurchaseAdjustmentCalculationType::class,
            'calculation_base' => PurchaseAdjustmentCalculationBase::class,
            'allocation_method' => PurchaseAdjustmentAllocationMethod::class,
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'returned_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'is_allocatable' => 'boolean',
            'finance_posting_profile_id' => 'integer',
            'finance_account_id' => 'integer',
            'sort_order' => 'integer',
        ]);
    }

    public function originAdjustment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origin_purchase_header_adjustment_id');
    }

    public function adjustmentAllocations(): HasMany
    {
        return $this->hasMany(PurchaseAdjustmentAllocation::class, 'purchase_header_adjustment_id');
    }

    public function targetAdjustmentAllocations(): HasMany
    {
        return $this->hasMany(PurchaseAdjustmentAllocation::class, 'target_purchase_header_adjustment_id');
    }
}
