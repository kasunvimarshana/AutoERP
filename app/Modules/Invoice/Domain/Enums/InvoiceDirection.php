<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
