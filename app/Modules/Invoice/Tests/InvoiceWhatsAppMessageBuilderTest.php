<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceWhatsAppMessageBuilder;
use Tests\TestCase;

final class InvoiceWhatsAppMessageBuilderTest extends TestCase
{
    public function test_it_builds_a_readable_financial_summary_with_the_pdf_url_on_its_own_line(): void
    {
        $invoice = new Invoice;
        $invoice->forceFill([
            'currency_code_snapshot' => 'LKR',
            'currency_symbol_snapshot' => null,
            'grand_total' => '12500.000000',
            'paid_total' => '5000.000000',
            'balance_due' => '7500.000000',
        ]);
        $url = 'https://erp.example.test/shared/invoices/1/pdf/1?expires=123&signature=abc';

        $message = app(InvoiceWhatsAppMessageBuilder::class)->build(
            invoice: $invoice,
            recipientName: 'Kasun',
            documentNumber: 'SERVICE-2026-000001',
            documentUrl: $url,
        );

        self::assertSame(
            "Hello Kasun,\n\n*Invoice SERVICE-2026-000001*\n\n"
            ."*Invoice total:* LKR 12,500.00\n"
            ."*Paid amount:* LKR 5,000.00\n"
            ."*Balance due:* LKR 7,500.00\n\n"
            ."*View or download PDF:*\n{$url}",
            $message,
        );
        self::assertStringNotContainsString('[', $message);
        self::assertStringNotContainsString('](', $message);
    }
}
