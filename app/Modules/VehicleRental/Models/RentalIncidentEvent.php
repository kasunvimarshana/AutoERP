<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class RentalIncidentEvent extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_incident_events';
}
