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

enum AgreementAdjustmentStatus: string
{
    case Draft = 'draft';
    case Authorized = 'authorized';
    case Applied = 'applied';
    case Voided = 'voided';
}
