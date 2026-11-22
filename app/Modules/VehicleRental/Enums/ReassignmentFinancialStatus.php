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

enum ReassignmentFinancialStatus: string
{
    case Pending = 'pending';
    case Settled = 'settled';
    case None = 'none';
}
