<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;

final class VehicleFinanceAgreement extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_finance_agreements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'supplier_id' => 'integer',
            'vehicle_id' => 'integer',
            'agreement_date' => 'date',
            'starts_at' => 'datetime',
            'matures_at' => 'datetime',
            'currency_id' => 'integer',
            'principal_amount' => 'decimal:6',
            'initial_deposit_amount' => 'decimal:6',
            'residual_value' => 'decimal:6',
            'annual_interest_rate' => 'decimal:6',
            'installment_count' => 'integer',
            'payment_term_days' => 'integer',
            'tax_group_id' => 'integer',
            'status' => VehicleFinanceAgreementStatus::class,
            'approved_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)
            ->when($organizationUnitId !== null, fn (Builder $scope) => $scope->where('organization_unit_id', $organizationUnitId));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'tax_group_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(VehicleFinanceInstallment::class, 'finance_agreement_id')->orderBy('installment_number');
    }

    public function history(): HasMany
    {
        return $this->hasMany(VehicleFinanceStatusHistory::class, 'finance_agreement_id')->orderBy('id');
    }

    protected static function booted(): void
    {
        self::deleting(static fn () => throw new LogicException('Vehicle finance agreements must be preserved for audit history. Cancel the agreement instead.'));
    }
}
