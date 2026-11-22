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

enum AgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
