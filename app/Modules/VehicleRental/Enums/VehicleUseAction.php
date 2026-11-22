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

enum VehicleUseAction: string
{
    case Plan = 'plan';
    case Handover = 'handover';
    case ReturnVehicle = 'return';
    case Cancel = 'cancel';
}
