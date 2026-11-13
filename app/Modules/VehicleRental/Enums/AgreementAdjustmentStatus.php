<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AgreementAdjustmentStatus: string
{
    case Draft = 'draft';
    case Authorized = 'authorized';
    case Applied = 'applied';
    case Voided = 'voided';
}
