<?php

namespace App\Modules\CRM\Events;

use App\Modules\CRM\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Vehicle Updated Event
 * 
 * Fired when a vehicle's information is updated
 * 
 * @package App\Modules\CRM\Events
 */
class VehicleUpdated
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
