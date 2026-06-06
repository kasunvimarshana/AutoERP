<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleDocumentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Pending = 'pending';
}
