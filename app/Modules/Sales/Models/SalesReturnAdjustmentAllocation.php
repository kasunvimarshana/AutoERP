<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;

final class SalesReturnAdjustmentAllocation extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'adjustment_type' => SalesAdjustmentType::class,
            'effect' => SalesAdjustmentEffect::class,
            'source_amount' => 'decimal:6',
            'previously_returned_amount' => 'decimal:6',
            'returned_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
        ]);
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(SalesHeaderAdjustment::class, 'sales_header_adjustment_id');
    }
}
