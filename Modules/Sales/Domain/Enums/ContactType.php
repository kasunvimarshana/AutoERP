<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum ContactType: string
{
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';
    case BOTH     = 'both';
}
