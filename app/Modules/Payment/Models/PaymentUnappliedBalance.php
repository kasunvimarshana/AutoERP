<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\UnappliedBalanceStatus;
use Modules\Tenant\Models\TenantModel;

final class PaymentUnappliedBalance extends TenantOwnedModel
{
    protected $table = 'payment_unapplied_balances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'party_id' => 'integer',
            'source_id' => 'integer',
            'allocation_status' => PaymentAllocationState::class,
            'original_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'status' => UnappliedBalanceStatus::class,
            'metadata' => 'array',
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
