<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

/** Rental observations only. Call inside a transaction after locking the physical Vehicle. */
final class OdometerContinuity
{
    private const CUSTODY_OBSERVATIONS = ['handed_over_at' => 'handover_odometer', 'returned_at' => 'return_odometer'];

    private const CHART_OBSERVATIONS = ['starts_at' => 'start_odometer', 'ends_at' => 'end_odometer'];

    public function assertReading(int $tenant, int $vehicle, string $at, ?string $reading): void
    {
        if ($reading === null) {
            return;
        }

        $uses = VehicleUse::query()->forTenant($tenant)->where('vehicle_id', $vehicle)
            ->whereIn('status', [VehicleUseStatus::InCustody->value, VehicleUseStatus::Returned->value]);
        $charts = RunningChart::query()->forTenant($tenant)->where('status', RunningChartStatus::Finalized->value)
            ->whereHas('vehicleUse', fn ($q) => $q->where('vehicle_id', $vehicle)->where('tenant_id', $tenant));

        foreach (self::CUSTODY_OBSERVATIONS as $timeColumn => $readingColumn) {
            $this->assertConsistent(clone $uses, $timeColumn, $readingColumn, $at, $reading);
        }
        foreach (self::CHART_OBSERVATIONS as $timeColumn => $readingColumn) {
            $this->assertConsistent(clone $charts, $timeColumn, $readingColumn, $at, $reading);
        }
    }

    private function assertConsistent(Builder $query, string $timeColumn, string $readingColumn, string $at, string $reading): void
    {
        // Check both directions for backfilled evidence. Nulls do not compare and are never filled.
        // A current locking read must see observations committed while waiting for the Vehicle lock.
        $conflict = $query->where(fn ($q) => $q
            ->where(fn ($earlier) => $earlier->where($timeColumn, '<=', $at)->where($readingColumn, '>', $reading))
            ->orWhere(fn ($later) => $later->where($timeColumn, '>=', $at)->where($readingColumn, '<', $reading)))
            ->lockForUpdate()->first(['id']);
        if ($conflict !== null) {
            throw ValidationException::withMessages(['odometer' => ['Reading conflicts with recorded custody or finalized usage for this vehicle. Review the evidence.']]);
        }
    }
}
