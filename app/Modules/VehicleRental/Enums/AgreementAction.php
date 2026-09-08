<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AgreementAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Activate = 'activate';
    case Close = 'close';
}
