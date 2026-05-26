<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\VehicleService\Domain\Events\JobCardCompleted;

final class UpdateVehicleStatusToActive
{
    public function handle(JobCardCompleted $event): void
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
            'status' => 'active',
            'last_service_date' => now()->toDateString(),
            'last_service_odometer' => $jobCard->end_odometer ?? $vehicle->last_service_odometer,
            'next_service_due_date' => $jobCard->next_service_date ?? $vehicle->next_service_due_date,
            'next_service_due_odometer' => $jobCard->next_service_odometer ?? $vehicle->next_service_due_odometer,
            'row_version' => (int) $vehicle->row_version + 1,
            'updated_at' => now(),
        ]);
    }
}
