<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization as HasTenantAndOrganizationTrait;

class VehicleRentalLesseeAgreement extends Model
{
    use HasTenantAndOrganizationTrait;
    use SoftDeletes;

    protected $table = 'vehicle_rental_lessee_agreements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'agreement_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'daily_rate' => 'decimal:4',
            'weekly_rate' => 'decimal:4',
            'monthly_rate' => 'decimal:4',
            'rate_per_km' => 'decimal:4',
            'excess_km_rate' => 'decimal:4',
            'maximum_number_of_km' => 'decimal:4',
            'start_odometer' => 'integer',
            'end_odometer' => 'integer',
            'driver_included' => 'boolean',
            'drivers_salary' => 'decimal:4',
            'working_hours_per_weekday' => 'decimal:4',
            'working_hours_per_saturday' => 'decimal:4',
            'working_hours_per_sunday' => 'decimal:4',
            'normal_ot_rate_per_hour' => 'decimal:4',
            'double_ot_rate_per_hour' => 'decimal:4',
            'night_out_rate_per_hour' => 'decimal:4',
            'driver_outstation_allowance' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function lessee(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Models\\Customer',
            'lessee_id'
        );
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Vehicle\\Infrastructure\\Persistence\\Eloquent\\Models\\Vehicle',
            'vehicle_id'
        );
    }

    public function rentalIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'rental_income_account_id'
        );
    }

    public function rentalExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'rental_expense_account_id'
        );
    }

    public function excessKmIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'excess_km_income_account_id'
        );
    }

    public function driverExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'driver_expense_account_id'
        );
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'created_by'
        );
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'updated_by'
        );
    }

    public function runningCharts(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLesseeRunningChart',
            'lessee_agreement_id'
        );
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLesseeAgreementCreditNote',
            'lessee_agreement_id'
        );
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleRental\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleRentalLesseeAgreementDebitNote',
            'lessee_agreement_id'
        );
    }
}
