<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Sales\Enums\SalesAdjustmentAllocationMethod;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;

final class SalesHeaderAdjustment extends CoreModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'adjustment_type' => SalesAdjustmentType::class,
            'effect' => SalesAdjustmentEffect::class,
            'calculation_type' => SalesAdjustmentCalculationType::class,
            'calculation_base' => SalesAdjustmentCalculationBase::class,
            'allocation_method' => SalesAdjustmentAllocationMethod::class,
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'returned_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'is_allocatable' => 'boolean',
        ]);
    }
}
