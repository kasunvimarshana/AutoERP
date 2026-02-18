<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum CashRegisterStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}
