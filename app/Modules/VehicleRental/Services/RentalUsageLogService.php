<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalUsageLogService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(RentalAgreement $agreement, RentalUsageLogData $data): RentalUsageLog
    {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException('Usage logs require an active or returned agreement.');
        }

        return DB::transaction(function () use ($agreement, $data): RentalUsageLog {
            $allocation = $agreement->vehicles()->findOrFail($data->agreementVehicleId);
            $this->validate($agreement, $allocation, $data);
            $distance = $this->math->sub($data->endOdometer, $data->startOdometer);
            $previous = RentalUsageLog::query()
                ->where('agreement_vehicle_id', $allocation->getKey())
                ->where('status', '!=', RentalUsageLogStatus::Rejected->value)
                ->orderByDesc('usage_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            $cumulative = $this->math->add((string) ($previous?->cumulative_km ?? '0.000000'), $distance);

            $log = RentalUsageLog::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'agreement_vehicle_id' => $allocation->getKey(),
                'vehicle_id' => $allocation->vehicle_id,
                'driver_id' => $data->driverId,
                'usage_date' => $data->usageDate,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'start_odometer' => $this->math->normalize($data->startOdometer),
                'end_odometer' => $this->math->normalize($data->endOdometer),
                'distance_km' => $distance,
                'cumulative_km' => $cumulative,
                'comparative_km' => $data->comparativeKm === null ? null : $this->math->normalize($data->comparativeKm),
                'trip_from' => $data->tripFrom,
                'trip_to' => $data->tripTo,
                'trip_purpose' => $data->tripPurpose,
                'status' => $data->status->value,
                'approved_by' => $data->status === RentalUsageLogStatus::Approved ? $data->approvedBy : null,
                'approved_at' => $data->status === RentalUsageLogStatus::Approved ? now() : null,
                'remarks' => $data->remarks,
            ]);

            return $log->load(['vehicle', 'driver', 'events']);
        });
    }

    public function changeStatus(
        RentalUsageLog $log,
        RentalUsageLogStatus $status,
        ?int $approvedBy = null,
    ): RentalUsageLog {
        $allowed = [
            'draft' => ['submitted', 'approved', 'rejected'],
            'submitted' => ['approved', 'rejected'],
            'approved' => [],
            'rejected' => ['draft'],
        ];
        $old = $log->status;
        if ($old === $status) {
            return $log;
        }
        if (! in_array($status->value, $allowed[$old->value] ?? [], true)) {
            throw new InvalidArgumentException(
                "Invalid usage log status transition from {$old->value} to {$status->value}.",
            );
        }
        $log->forceFill([
            'status' => $status->value,
            'approved_by' => $status === RentalUsageLogStatus::Approved ? $approvedBy : null,
            'approved_at' => $status === RentalUsageLogStatus::Approved ? now() : null,
        ])->save();

        return $log->refresh();
    }

    private function validate(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        RentalUsageLogData $data,
    ): void {
        if ($this->math->compare($data->endOdometer, $data->startOdometer) < 0) {
            throw new InvalidArgumentException('Usage end odometer must be greater than or equal to start odometer.');
        }
        if ($this->math->compare($data->startOdometer, (string) $allocation->start_odometer) < 0) {
            throw new InvalidArgumentException('Usage start odometer cannot be below the vehicle pickup odometer.');
        }
        if (! $allocation->pickupInspection()->exists()) {
            throw new InvalidArgumentException('Pickup inspection is required before recording vehicle usage.');
        }
        $date = CarbonImmutable::parse($data->usageDate);
        if ($date->startOfDay()->lessThan($allocation->allocated_from->startOfDay())
            || ($allocation->allocated_to !== null && $date->startOfDay()->greaterThan($allocation->allocated_to->startOfDay()))) {
            throw new InvalidArgumentException('Usage date must fall within the vehicle allocation period.');
        }
        $duplicate = RentalUsageLog::query()
            ->where('agreement_vehicle_id', $allocation->getKey())
            ->whereDate('usage_date', $data->usageDate)
            ->where(function (Builder $query) use ($data): void {
                $data->startTime === null
                    ? $query->whereNull('start_time')
                    : $query->where('start_time', $data->startTime);
            })
            ->where('status', '!=', RentalUsageLogStatus::Rejected->value)
            ->exists();
        if ($duplicate) {
            throw new InvalidArgumentException('A usage log already exists for this vehicle, date, and start time.');
        }
        if (($data->startTime === null) !== ($data->endTime === null)) {
            throw new InvalidArgumentException('Usage start time and end time must be provided together.');
        }
        if ($data->driverId !== null) {
            $driver = HrEmployee::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where(function (Builder $query) use ($agreement): void {
                    $query->whereNull('organization_unit_id');
                    if ($agreement->organization_unit_id !== null) {
                        $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                    }
                })
                ->findOrFail($data->driverId);
            if ($driver->status !== EmployeeStatus::Active) {
                throw new InvalidArgumentException('Only active employees can be assigned as rental drivers.');
            }
        }
    }
}
