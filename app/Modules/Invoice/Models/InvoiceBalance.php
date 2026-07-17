<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceBalance extends TenantOwnedModel
{
    protected $table = 'invoice_balances';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_total' => 'decimal:6',
            'paid_amount' => 'decimal:6',
            'credit_allocated_amount' => 'decimal:6',
            'debit_allocated_amount' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
            'status' => InvoiceBalanceStatus::class,
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
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
