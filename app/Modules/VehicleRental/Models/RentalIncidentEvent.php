<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Models;

final class RentalIncidentEvent extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_incident_events';
}
