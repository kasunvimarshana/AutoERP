<?php

declare(strict_types=1);

namespace Modules\Sales\Tests;

use PHPUnit\Framework\TestCase;

final class FastSalesPaymentBoundaryTest extends TestCase
{
    public function test_receipts_use_the_payment_owned_lifecycle(): void
    {
        $service = file_get_contents(__DIR__.'/../Services/FastSalesService.php');
        $documents = file_get_contents(__DIR__.'/../Services/Concerns/CreatesFastSalesDocuments.php');

        self::assertIsString($service);
        self::assertIsString($documents);
        self::assertStringContainsString('PaymentDocumentLifecycleService', $service);
        self::assertStringContainsString('PaymentPostingService', $service);
        self::assertStringContainsString('$this->paymentDocuments->submit', $documents);
        self::assertStringContainsString('$this->paymentDocuments->approve', $documents);
        self::assertStringContainsString('$this->paymentPostings', $documents);
        self::assertStringNotContainsString('PaymentStatus', $service.$documents);
        self::assertStringNotContainsString('postPaymentFinance', $service.$documents);
        self::assertStringNotContainsString('destination_account_id', $service.$documents);
        self::assertStringNotContainsString('payment_accounts', $service.$documents);
        self::assertStringNotContainsString('requires_bank_account', $service.$documents);
    }

    public function test_ui_does_not_request_a_finance_deposit_account(): void
    {
        $root = dirname(__DIR__, 4);
        $page = file_get_contents($root.'/resources/js/modules/sales/pages/FastSalesPage.tsx');

        self::assertIsString($page);
        self::assertStringNotContainsString('Deposit account', $page);
        self::assertStringNotContainsString('destination_account_id', $page);
    }
}
