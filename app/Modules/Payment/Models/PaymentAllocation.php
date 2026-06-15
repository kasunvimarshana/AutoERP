<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Tenant\Models\TenantModel;

final class PaymentAllocation extends CoreModel
{
    protected $table = 'payment_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_total' => 'decimal:6',
            'invoice_balance_before' => 'decimal:6',
            'previously_allocated_amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'invoice_balance_after' => 'decimal:6',
            'allocation_date' => 'date',
            'status' => AllocationStatus::class,
            'metadata' => 'array',
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        /** @var class-string<Model> $invoiceModel */
        $invoiceModel = 'Modules\\'.'Invoice\\Models\\Invoice';

        return $this->belongsTo($invoiceModel, 'invoice_id');
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
