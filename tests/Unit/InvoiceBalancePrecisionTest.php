<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceUpdater;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseDebitNoteService;
use Tests\TestCase;

/**
 * The exact-decimal entrypoints must reject unrepresentable money before
 * opening a transaction or interacting with any database records.
 */
final class InvoiceBalancePrecisionTest extends TestCase
{
    public function test_invoice_payment_rejects_sub_scale_fraction_before_persistence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the supported scale');

        app(InvoiceBalanceUpdater::class)->applyPayment(new Invoice, '1.0000001');
    }

    public function test_invoice_payment_reversal_rejects_sub_scale_fraction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the supported scale');

        app(InvoiceBalanceUpdater::class)->reversePayment(new Invoice, '1.0000001');
    }

    public function test_invoice_credit_allocation_rejects_sub_scale_fraction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the supported scale');

        app(InvoiceBalanceUpdater::class)->allocateCredit(new Invoice, 'purchase_debit_note', 1, '1.0000001');
    }

    public function test_purchase_debit_note_rejects_sub_scale_fraction_before_invoice_mutation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the supported scale');

        app(PurchaseDebitNoteService::class)->allocate(
            new PurchaseDebitNote, new Invoice, '1.0000001', 1,
        );
    }
}
