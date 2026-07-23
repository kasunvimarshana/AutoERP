<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceDocumentKind: string
{
    case TaxInvoice = 'tax_invoice';
    case Invoice = 'invoice';
    case PurchaseInvoice = 'purchase_invoice';
    case OwnerPayableVoucher = 'owner_payable_voucher';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';

    public function title(): string
    {
        return match ($this) {
            self::TaxInvoice => 'Tax Invoice',
            self::Invoice => 'Invoice',
            self::PurchaseInvoice => 'Purchase Invoice',
            self::OwnerPayableVoucher => 'Owner Payable Voucher',
            self::CreditNote => 'Credit Note',
            self::DebitNote => 'Debit Note',
        };
    }

    public function numberLabel(): string
    {
        return match ($this) {
            self::TaxInvoice => 'Tax Invoice No.',
            self::Invoice => 'Invoice No.',
            self::PurchaseInvoice => 'Purchase Invoice No.',
            self::OwnerPayableVoucher => 'Owner Payable Voucher No.',
            self::CreditNote => 'Credit Note No.',
            self::DebitNote => 'Debit Note No.',
        };
    }
}
