<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum IncidentPermission: string
{
    case View = 'vehicle-rental.incidents.view';
    case Record = 'vehicle-rental.incidents.record';
    case Review = 'vehicle-rental.incidents.review';
}
