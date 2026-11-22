<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Enums;

enum PaymentMethodDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Both = 'both';
}
