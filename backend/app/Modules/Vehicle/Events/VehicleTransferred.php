<?php

namespace App\Modules\Vehicle\Events;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleTransferred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Vehicle $vehicle,
        public string $oldCustomerId,
        public string $newCustomerId
    ) {}
}
