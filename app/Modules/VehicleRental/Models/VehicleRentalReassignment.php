<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\HR\Models\HrEmployee;
use Modules\User\Models\UserModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\ReassignmentFinancialStatus;
use Modules\VehicleRental\Enums\ReassignmentType;

final class VehicleRentalReassignment extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_reassignments';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'reassignment_type' => ReassignmentType::class,
            'financial_status' => ReassignmentFinancialStatus::class,
            'financial_delta_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'occurred_at' => 'immutable_datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenant): Builder
    {
        return $query->forTenant($tenant);
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

    public function previousVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'previous_vehicle_id')->withTrashed();
    }

    public function newVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'new_vehicle_id')->withTrashed();
    }

    public function previousOwnerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class, 'previous_owner_agreement_id');
    }

    public function newOwnerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class, 'new_owner_agreement_id');
    }

    public function previousDriver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'previous_driver_employee_id');
    }

    public function newDriver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'new_driver_employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
