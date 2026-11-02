<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;

final class VehicleFinanceInstallment extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_finance_installments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'finance_agreement_id' => 'integer',
            'installment_number' => 'integer',
            'due_date' => 'date',
            'principal_due' => 'decimal:6',
            'interest_due' => 'decimal:6',
            'fee_due' => 'decimal:6',
            'tax_due' => 'decimal:6',
            'total_due' => 'decimal:6',
            'paid_amount' => 'decimal:6',
            'balance_due' => 'decimal:6',
            'status' => VehicleFinanceInstallmentStatus::class,
            'invoice_id' => 'integer',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)
            ->when($organizationUnitId !== null, fn (Builder $scope) => $scope->where('organization_unit_id', $organizationUnitId));
    }

    public function financeAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleFinanceAgreement::class, 'finance_agreement_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
