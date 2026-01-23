<?php

namespace App\Modules\FleetManagement\Events;

use App\Modules\FleetManagement\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleAddedToFleet
{
    use Dispatchable, SerializesModels;

    public Vehicle $vehicle;

    public function __construct(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
    }
}
