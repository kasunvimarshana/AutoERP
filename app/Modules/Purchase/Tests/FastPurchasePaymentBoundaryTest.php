<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use PHPUnit\Framework\TestCase;

final class FastPurchasePaymentBoundaryTest extends TestCase
{
    public function test_supplier_payments_use_the_payment_owned_lifecycle(): void
    {
        $service = file_get_contents(__DIR__.'/../Services/FastPurchaseService.php');
        $coordinator = file_get_contents(__DIR__.'/../Services/FastPurchasePostingCoordinator.php');
        $documents = file_get_contents(__DIR__.'/../Services/FastPurchaseDocumentBuilder.php');
        $integration = file_get_contents(__DIR__.'/../Services/PurchasePaymentIntegrationService.php');
        $request = file_get_contents(__DIR__.'/../Http/Requests/FastPurchaseRequest.php');

        self::assertIsString($service);
        self::assertIsString($coordinator);
        self::assertIsString($documents);
        self::assertIsString($integration);
        self::assertIsString($request);
        self::assertStringContainsString('$this->purchasePayments->createSupplierPayment', $documents);
        self::assertStringContainsString('$this->lifecycle->submit', $integration);
        self::assertStringContainsString('$this->lifecycle->approve', $integration);
        self::assertStringContainsString('$this->posting->post', $integration);
        self::assertStringNotContainsString('postPaymentFinance', $coordinator);
        self::assertStringNotContainsString('internalBankAccountId', $service);
        self::assertStringNotContainsString('requires_bank_account', $service);
        self::assertStringNotContainsString('payment_accounts', $service);
        self::assertStringNotContainsString('source_accounts', $service.$coordinator);
        self::assertStringContainsString("'payment.source_account_id'", $request);
        self::assertStringContainsString("'payment.lines.*.source_account_id'", $request);
    }

    public function test_fast_purchase_ui_submits_payment_facts_without_finance_accounts(): void
    {
        $root = dirname(__DIR__, 4);
        $editor = file_get_contents($root.'/resources/js/modules/purchase/components/PurchasePaymentMethodsEditor.tsx');
        $sections = file_get_contents($root.'/resources/js/modules/purchase/components/FastPurchaseSections.tsx');
        $form = file_get_contents($root.'/resources/js/modules/purchase/hooks/useFastPurchaseForm.ts');
        $types = file_get_contents($root.'/resources/js/modules/purchase/types/fastPurchaseTypes.ts');

        self::assertIsString($editor);
        self::assertIsString($sections);
        self::assertIsString($form);
        self::assertIsString($types);
        self::assertStringNotContainsString('source_account_id', $editor.$sections.$form.$types);
        self::assertStringNotContainsString('payment_accounts', $editor.$sections.$form.$types);
        self::assertStringNotContainsString('requires_bank_account', $editor.$sections.$form.$types);
        self::assertStringContainsString('requires_instrument_details', $types);
    }
}
