<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageLog extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_logs';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_allocation_id' => 'integer',
            'vehicle_id' => 'integer',
            'driver_assignment_id' => 'integer',
            'driver_id' => 'integer',
            'usage_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'start_odometer' => 'decimal:6',
            'end_odometer' => 'decimal:6',
            'distance_km' => 'decimal:6',
            'net_operational_distance_km' => 'decimal:6',
            'garage_distance_km' => 'decimal:6',
            'internal_distance_km' => 'decimal:6',
            'working_minutes' => 'integer',
            'normal_overtime_minutes' => 'integer',
            'double_overtime_minutes' => 'integer',
            'triple_overtime_minutes' => 'integer',
            'night_out_count' => 'decimal:6',
            'operational_sequence' => 'integer',
            'status' => RentalUsageStatus::class,
            'fingerprint_sequence' => 'integer',
            'metadata' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function allocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'vehicle_allocation_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function driverAssignment(): BelongsTo { return $this->belongsTo(RentalDriverAssignment::class, 'driver_assignment_id'); }
    public function driver(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'driver_id'); }
    public function events(): HasMany { return $this->hasMany(RentalUsageEvent::class, 'usage_log_id')->orderBy('sequence'); }
    public function contexts(): HasMany { return $this->hasMany(RentalUsageContext::class, 'usage_log_id'); }
    public function facts(): HasMany { return $this->hasMany(RentalUsageFact::class, 'usage_log_id'); }
}
