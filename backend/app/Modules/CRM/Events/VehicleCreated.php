<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Vehicle Created Event
 * 
 * Fired when a new vehicle is created in the system
 * 
 * @package App\Modules\CRM\Events
 */
class VehicleCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param Vehicle $vehicle
     */
    public function __construct(public Vehicle $vehicle)
    {
    }
}
