<?php

declare(strict_types=1);

namespace Modules\Voucher\Enums;

enum VoucherSourceModule: string
{
    case Payment = 'Payment';
    case Finance = 'Finance';
}
