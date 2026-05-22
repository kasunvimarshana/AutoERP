<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum AdvancePaymentType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
}
