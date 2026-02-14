<?php

namespace App\Modules\Vehicle\Events;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Vehicle $vehicle
    ) {}
}
