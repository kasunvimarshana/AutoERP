<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Tenant\Models\TenantModel;

final class PaymentAllocation extends TenantOwnedModel
{
    public const ACTIVE_IDENTITY_SLOT = 1;

    protected $table = 'payment_allocations';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'invoice_id' => 'integer',
            'active_identity_slot' => 'integer',
            'invoice_date_snapshot' => 'date',
            'invoice_total' => 'decimal:6',
            'invoice_balance_before' => 'decimal:6',
            'previously_allocated_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'invoice_balance_after' => 'decimal:6',
            'allocation_date' => 'date',
            'status' => AllocationStatus::class,
            'realized_at' => 'datetime',
            'realized_by' => 'integer',
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
