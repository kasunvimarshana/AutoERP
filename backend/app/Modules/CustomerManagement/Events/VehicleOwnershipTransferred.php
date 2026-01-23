<?php

namespace App\Modules\CustomerManagement\Events;

use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleOwnershipTransferred
{
    use Dispatchable, SerializesModels;

    public Vehicle $vehicle;
    public int $previousCustomerId;
    public int $newCustomerId;

    public function __construct(Vehicle $vehicle, int $previousCustomerId, int $newCustomerId)
    {
        $this->vehicle = $vehicle;
        $this->previousCustomerId = $previousCustomerId;
        $this->newCustomerId = $newCustomerId;
    }
}
