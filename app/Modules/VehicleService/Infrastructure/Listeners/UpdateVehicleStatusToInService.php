<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\VehicleService\Domain\Events\JobCardCreated;

final class UpdateVehicleStatusToInService
{
    public function handle(JobCardCreated $event): void
    {
        $jobCard = DB::table('vehicle_service_job_cards')->find($event->jobCardId);
        if ($jobCard === null || $jobCard->vehicle_id === null) {
            return;
        }

        $vehicle = DB::table('vehicles')->lockForUpdate()->find((int) $jobCard->vehicle_id);
        if ($vehicle === null) {
            return;
        }

        DB::table('vehicles')->where('id', (int) $vehicle->id)->update([
            'status' => 'in_service',
            'row_version' => (int) $vehicle->row_version + 1,
            'updated_at' => now(),
        ]);
    }
}
