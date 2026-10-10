<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentCardBrand: string
{
    case Visa = 'visa';
    case Master = 'master';
    case Amex = 'amex';
}
