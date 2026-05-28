<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Constants;

final class VoucherErrorCode
{
    public const INVALID_VALUE = 'VOUCHER_INVALID_VALUE';
    public const NOT_FOUND = 'VOUCHER_NOT_FOUND';
    public const CONFLICT = 'VOUCHER_CONFLICT';
    public const INVALID_STATUS_TRANSITION = 'VOUCHER_INVALID_STATUS_TRANSITION';
    public const UNBALANCED_LINES = 'VOUCHER_UNBALANCED_LINES';
    public const PAYMENT_METHOD_INVALID = 'VOUCHER_PAYMENT_METHOD_INVALID';
    public const ACCOUNT_NOT_POSTABLE = 'VOUCHER_ACCOUNT_NOT_POSTABLE';
    public const INTEGRATION_FAILED = 'VOUCHER_INTEGRATION_FAILED';
}
