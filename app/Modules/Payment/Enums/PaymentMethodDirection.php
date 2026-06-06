<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentMethodDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Both = 'both';
}
