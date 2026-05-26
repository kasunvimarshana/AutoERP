<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Constants;

final class PaymentErrorCode
{
    public const INVALID_VALUE = 'PAYMENT_INVALID_VALUE';
    public const NOT_FOUND = 'PAYMENT_NOT_FOUND';
    public const CONFLICT = 'PAYMENT_CONFLICT';
    public const INSUFFICIENT_UNALLOCATED_AMOUNT = 'PAYMENT_INSUFFICIENT_UNALLOCATED_AMOUNT';
    public const INVALID_STATUS_TRANSITION = 'PAYMENT_INVALID_STATUS_TRANSITION';
}
