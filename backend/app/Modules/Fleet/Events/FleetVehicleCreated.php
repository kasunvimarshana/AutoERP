<?php

namespace App\Modules\Fleet\Events;

use App\Modules\Fleet\Models\FleetVehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FleetVehicleCreated
{
    use Dispatchable, SerializesModels;

    public FleetVehicle $fleetVehicle;

    public function __construct(FleetVehicle $fleetVehicle)
    {
        $this->fleetVehicle = $fleetVehicle;
    }
}
