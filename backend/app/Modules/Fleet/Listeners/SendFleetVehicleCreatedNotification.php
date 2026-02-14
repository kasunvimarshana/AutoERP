<?php

namespace App\Modules\Fleet\Listeners;

use App\Modules\Fleet\Events\FleetVehicleCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFleetVehicleCreatedNotification implements ShouldQueue
{
    public function handle(FleetVehicleCreated $event): void
    {
        // Implement notification logic here
    }
}
