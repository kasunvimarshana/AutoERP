<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

final class RentalIncidentAttachment extends ImmutableRentalHistory
{
    protected $table = 'vehicle_rental_incident_attachments';

    protected $hidden = ['object_key'];
}
