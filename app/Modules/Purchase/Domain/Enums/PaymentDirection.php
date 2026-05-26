<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum PaymentDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
