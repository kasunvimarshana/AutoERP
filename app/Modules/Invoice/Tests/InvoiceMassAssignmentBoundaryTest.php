<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Modules\Invoice\Models\Invoice;
use PHPUnit\Framework\TestCase;

final class InvoiceMassAssignmentBoundaryTest extends TestCase
{
    public function test_invoice_is_totally_guarded_and_owner_service_writes_explicitly(): void
    {
        $invoice = new Invoice();
        $service = file_get_contents(dirname(__DIR__).'/Services/InvoiceCreationService.php');

        self::assertSame(['*'], $invoice->getGuarded());
        self::assertFalse($invoice->isFillable('status'));
        self::assertIsString($service);
        self::assertStringContainsString('$invoice = new Invoice();', $service);
        self::assertStringContainsString('$invoice->forceFill([', $service);
        self::assertStringNotContainsString('Invoice::query()->create([', $service);
    }
}
