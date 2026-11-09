<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;

final class RentalExpense extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_expenses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'customer_agreement_id' => 'integer',
            'vehicle_use_id' => 'integer',
            'running_chart_id' => 'integer',
            'vehicle_id' => 'integer',
            'supplier_id' => 'integer',
            'employee_id' => 'integer',
            'expense_type' => RentalExpenseType::class,
            'expense_date' => 'date',
            'currency_id' => 'integer',
            'net_amount' => 'decimal:6',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:6',
            'gross_amount' => 'decimal:6',
            'status' => RentalExpenseStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reversed_at' => 'datetime',
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

    public function customerAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class, 'customer_agreement_id');
    }

    public function vehicleUse(): BelongsTo
    {
        return $this->belongsTo(VehicleUse::class, 'vehicle_use_id');
    }

    public function runningChart(): BelongsTo
    {
        return $this->belongsTo(RunningChart::class, 'running_chart_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id')->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'tax_group_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RentalExpenseAllocation::class, 'expense_id')->orderBy('sequence');
    }

    protected static function booted(): void
    {
        self::deleting(static fn () => throw new LogicException('Rental expenses must be preserved for audit history. Reject or reverse the expense instead.'));
    }
}
