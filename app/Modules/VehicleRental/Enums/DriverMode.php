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

enum DriverMode: string
{
    case SelfDrive = 'self_drive';
    case WithDriver = 'with_driver';
}
