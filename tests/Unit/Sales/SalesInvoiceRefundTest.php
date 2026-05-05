<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use Modules\Sales\Domain\Entities\SalesInvoice;
use PHPUnit\Framework\TestCase;

class SalesInvoiceRefundTest extends TestCase
{
    public function test_record_refund_reduces_paid_amount_and_sets_partial_paid_status(): void
    {
        $invoice = new SalesInvoice(
            tenantId: 1,
            customerId: 10,
            currencyId: 1,
            invoiceDate: new \DateTimeImmutable('2026-01-01'),
            dueDate: new \DateTimeImmutable('2026-01-31'),
            status: 'paid',
            grandTotal: '100.000000',
            paidAmount: '100.000000',
        );

        $invoice->recordRefund('25.000000');

        $this->assertSame('75.000000', $invoice->getPaidAmount());
        $this->assertSame('partial_paid', $invoice->getStatus());
    }

    public function test_record_refund_to_zero_paid_amount_sets_status_to_sent(): void
    {
        $invoice = new SalesInvoice(
            tenantId: 1,
            customerId: 10,
            currencyId: 1,
            invoiceDate: new \DateTimeImmutable('2026-01-01'),
            dueDate: new \DateTimeImmutable('2026-01-31'),
            status: 'partial_paid',
            grandTotal: '200.000000',
            paidAmount: '40.000000',
        );

        $invoice->recordRefund('40.000000');

        $this->assertSame('0.000000', $invoice->getPaidAmount());
        $this->assertSame('sent', $invoice->getStatus());
    }

    public function test_record_refund_cannot_exceed_paid_amount(): void
    {
        $invoice = new SalesInvoice(
            tenantId: 1,
            customerId: 10,
            currencyId: 1,
            invoiceDate: new \DateTimeImmutable('2026-01-01'),
            dueDate: new \DateTimeImmutable('2026-01-31'),
            status: 'partial_paid',
            grandTotal: '200.000000',
            paidAmount: '20.000000',
        );

        $this->expectException(\InvalidArgumentException::class);

        $invoice->recordRefund('21.000000');
    }
}
