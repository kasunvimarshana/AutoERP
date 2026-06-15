<?php

declare(strict_types=1);

namespace Modules\Voucher\Enums;

enum VoucherType: string
{
    case Receipt = 'receipt_voucher';
    case Payment = 'payment_voucher';
    case Journal = 'journal_voucher';
    case Contra = 'contra_voucher';
    case Adjustment = 'adjustment_voucher';
    case OpeningBalance = 'opening_balance_voucher';
    case Reversal = 'reversal_voucher';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt Voucher',
            self::Payment => 'Payment Voucher',
            self::Journal => 'Journal Voucher',
            self::Contra => 'Contra Voucher',
            self::Adjustment => 'Adjustment Voucher',
            self::OpeningBalance => 'Opening Balance Voucher',
            self::Reversal => 'Reversal Voucher',
        };
    }
}
