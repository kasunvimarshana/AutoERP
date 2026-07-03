<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceInspection;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceInspectionService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceStatusService $statuses,
    ) {}

    public function save(
        VehicleServiceJob $job,
        VehicleServiceInspectionData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceInspection
    {
        return DB::transaction(function () use ($job, $data, $expectedVersion): VehicleServiceInspection {
            $job = VehicleServiceJob::query()->with('inspection')->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->validator->assertMutable($job);
            if ($data->odometerReading !== null) {
                $this->validator->nonNegative($data->odometerReading, 'Odometer reading cannot be negative.');
            }

            $inspection = VehicleServiceInspection::query()->updateOrCreate(
                ['vehicle_service_job_id' => $job->getKey()],
                [
                    'tenant_id' => $job->tenant_id,
                    'organization_unit_id' => $job->organization_unit_id,
                    'customer_complaint' => $data->customerComplaint,
                    'inspection_notes' => $data->inspectionNotes,
                    'diagnosis' => $data->diagnosis,
                    'recommended_work' => $data->recommendedWork,
                    'odometer_reading' => $data->odometerReading === null ? null : $this->math->normalize($data->odometerReading),
                    'fuel_level' => $data->fuelLevel,
                    'inspected_by' => $data->inspectedBy,
                    'inspected_at' => $data->markInspected ? now() : $job->inspection?->inspected_at,
                ],
            );

            $statusChanged = false;
            if ($data->markInspected && $job->status === VehicleServiceJobStatus::Draft) {
                $this->statuses->change($job, VehicleServiceJobStatus::Inspected, $data->inspectedBy);
                $statusChanged = true;
            }
            if (! $statusChanged) {
                $this->bumpJobVersion($job);
            }

            return $inspection->refresh()->load('inspector');
        });
    }
}
