<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class PurchaseAdjustmentAllocation extends CoreModel
{
    protected $table = 'purchase_adjustment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_header_adjustment_id' => 'integer',
            'target_purchase_header_adjustment_id' => 'integer',
            'source_id' => 'integer',
            'target_id' => 'integer',
            'target_line_id' => 'integer',
            'basis_amount' => 'decimal:6',
            'source_amount' => 'decimal:6',
            'signed_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'recognized_at_grn_amount' => 'decimal:6',
            'recognized_at_invoice_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'finance_posting_profile_id' => 'integer',
            'finance_account_id' => 'integer',
            'provenance' => 'array',
        ]);
    }

    public function sourceAdjustment(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeaderAdjustment::class, 'purchase_header_adjustment_id');
    }

    public function targetAdjustment(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeaderAdjustment::class, 'target_purchase_header_adjustment_id');
    }
}
