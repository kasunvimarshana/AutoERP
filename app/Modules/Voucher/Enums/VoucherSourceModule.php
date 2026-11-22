<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Voucher\Enums;

enum VoucherSourceModule: string
{
    case Payment = 'Payment';
    case Finance = 'Finance';
}
