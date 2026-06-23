<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final class PurchaseReturnAdjustmentAllocation extends TenantOwnedModel
{
    protected $table = 'purchase_return_adjustment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_return_id' => 'integer',
            'purchase_header_adjustment_id' => 'integer',
            'adjustment_type' => PurchaseAdjustmentType::class,
            'effect' => PurchaseAdjustmentEffect::class,
            'source_amount' => 'decimal:6',
            'previously_returned_amount' => 'decimal:6',
            'returned_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
        ]);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeaderAdjustment::class, 'purchase_header_adjustment_id');
    }
}
