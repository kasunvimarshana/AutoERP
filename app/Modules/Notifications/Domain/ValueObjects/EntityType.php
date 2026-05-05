<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\ValueObjects;

enum EntityType: string
{
    case Rental       = 'rental';
    case ServiceJob   = 'service_job';
    case Vehicle      = 'vehicle';
    case Driver       = 'driver';
    case ReturnRefund = 'return_refund';
}
