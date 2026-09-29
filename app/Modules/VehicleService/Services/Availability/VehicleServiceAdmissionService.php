<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Availability;

use Modules\Vehicle\Services\VehicleAvailabilityService;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceAdmissionService
{
    private const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly VehicleAvailabilityService $availability, private readonly VehicleServiceAvailabilityBlocker $selfBlocker) {}

    public function assertAdmissible(VehicleServiceJob $job): void
    {
        // Workshop periods use inclusive dates. Convert to an exclusive next-midnight endpoint for external publishers.
        $this->availability->assertUnblocked((int) $job->tenant_id, $job->organization_unit_id, (int) $job->vehicle_id,
            $job->job_date->copy()->startOfDay()->format(self::TIMESTAMP_FORMAT),
            $job->expected_delivery_date?->copy()->addDay()->startOfDay()->format(self::TIMESTAMP_FORMAT), $this->selfBlocker);
    }
}
