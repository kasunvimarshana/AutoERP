<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum IncidentStatus: string
{
    case Recorded = 'recorded';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
