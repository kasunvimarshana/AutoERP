<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

class VehicleRentalLessorAgreementModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope, SoftDeletes;

    protected $table = 'vehicle_rental_lessor_agreements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'agreement_date' => 'date',
            'created_by' => 'integer',
            'daily_rate' => 'decimal:4',
            'double_ot_rate_per_hour' => 'decimal:4',
            'driver_expense_account_id' => 'integer',
            'driver_included' => 'boolean',
            'driver_night_out_allowance' => 'decimal:4',
            'driver_outstation_allowance' => 'decimal:4',
            'driver_salary' => 'decimal:4',
            'end_date' => 'date',
            'end_odometer' => 'integer',
            'excess_km_income_account_id' => 'integer',
            'excess_km_rate' => 'decimal:4',
            'lessor_id' => 'integer',
            'maximum_number_of_km' => 'decimal:4',
            'metadata' => 'array',
            'monthly_rate' => 'decimal:4',
            'normal_ot_rate_per_hour' => 'decimal:4',
            'organization_unit_id' => 'integer',
            'rate_per_km' => 'decimal:4',
            'rental_expense_account_id' => 'integer',
            'rental_income_account_id' => 'integer',
            'row_version' => 'integer',
            'start_date' => 'date',
            'start_odometer' => 'integer',
            'tenant_id' => 'integer',
            'updated_by' => 'integer',
            'vehicle_id' => 'integer',
            'weekly_rate' => 'decimal:4',
            'working_hours_per_saturday' => 'decimal:4',
            'working_hours_per_sunday' => 'decimal:4',
            'working_hours_per_weekday' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function lessor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'lessor_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

    public function rentalIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'rental_income_account_id');
    }

    public function rentalExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'rental_expense_account_id');
    }

    public function excessKmIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'excess_km_income_account_id');
    }

    public function driverExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'driver_expense_account_id');
    }

    public function vehicleRentalLessorRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorRunningChartModel::class, 'lessor_agreement_id');
    }

    public function vehicleRentalLesseeRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeRunningChartModel::class, 'lessor_agreement_id');
    }

    public function vehicleRentalLessorAgreementCreditNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementCreditNoteModel::class, 'lessor_agreement_id');
    }

    public function vehicleRentalLessorAgreementDebitNotes(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementDebitNoteModel::class, 'lessor_agreement_id');
    }
}

