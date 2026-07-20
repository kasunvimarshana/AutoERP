<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\VehicleRental\Enums\RentalAcMode;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

final class RentalRunningChart extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_running_charts';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'assignment_id' => 'integer',
            'replaces_running_chart_id' => 'integer',
            'operational_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'driver_employee_id' => 'integer',
            'ac_mode' => RentalAcMode::class,
            'start_odometer' => 'decimal:6',
            'end_odometer' => 'decimal:6',
            'total_km' => 'decimal:6',
            'garage_km' => 'decimal:6',
            'commercial_km' => 'decimal:6',
            'normal_overtime_hours' => 'decimal:6',
            'double_overtime_hours' => 'decimal:6',
            'triple_overtime_hours' => 'decimal:6',
            'night_out_count' => 'integer',
            'status' => RentalRunningChartStatus::class,
            'active_marker' => 'boolean',
            'created_by' => 'integer',
            'finalized_by' => 'integer',
            'finalized_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RentalAssignment::class, 'assignment_id');
    }

    public function replacesRunningChart(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_running_chart_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'driver_employee_id');
    }

    public function calculationSources(): HasMany
    {
        return $this->hasMany(RentalCalculationSource::class, 'running_chart_id');
    }
}
