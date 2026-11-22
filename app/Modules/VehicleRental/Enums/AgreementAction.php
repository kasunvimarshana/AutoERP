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

enum AgreementAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Activate = 'activate';
    case Close = 'close';
    case Supersede = 'supersede';
}
