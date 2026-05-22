<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Enums;

enum VoucherStatus: string
{
    case Draft = 'DRAFT';
    case Posted = 'POSTED';
    case Paid = 'PAID';
    case Void = 'VOID';
}
