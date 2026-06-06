<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
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
            'adjustment_type' => PurchaseAdjustmentType::class,
            'effect' => PurchaseAdjustmentEffect::class,
            'calculation_type' => PurchaseAdjustmentCalculationType::class,
            'allocation_method' => PurchaseAdjustmentAllocationMethod::class,
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'returned_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'is_allocatable' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }
}
