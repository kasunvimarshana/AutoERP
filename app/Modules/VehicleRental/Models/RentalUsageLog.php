<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageLog extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_logs';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'agreement_vehicle_id' => 'integer',
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'usage_date' => 'date',
            'effective_at' => 'datetime',
            'operational_sequence' => 'integer',
            'working_minutes' => 'integer',
            'start_odometer' => 'decimal:6',
            'end_odometer' => 'decimal:6',
            'distance_km' => 'decimal:6',
            'cumulative_km' => 'decimal:6',
            'comparative_km' => 'decimal:6',
            'status' => RentalUsageLogStatus::class,
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'submitted_by' => 'integer',
            'submitted_at' => 'datetime',
            'rejected_by' => 'integer',
            'rejected_at' => 'datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function agreementVehicle(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicle::class, 'agreement_vehicle_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'driver_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RentalUsageEvent::class, 'usage_log_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(RentalExpense::class, 'usage_log_id');
    }

    public function contexts(): HasMany
    {
        return $this->hasMany(RentalUsageContext::class, 'usage_log_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RentalStatusHistory::class, 'usage_log_id')->latest('changed_at');
    }
}
