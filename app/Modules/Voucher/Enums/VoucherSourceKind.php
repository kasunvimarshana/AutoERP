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

enum VoucherSourceKind: string
{
    case Payment = 'payment';
    case PaymentReversal = 'payment_reversal';
    case FinanceJournal = 'finance_journal';

    public function isPaymentSource(): bool
    {
        return $this === self::Payment || $this === self::PaymentReversal;
    }
}
