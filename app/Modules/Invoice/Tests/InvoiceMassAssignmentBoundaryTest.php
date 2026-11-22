<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Tests;

use Modules\Invoice\Models\Invoice;
use PHPUnit\Framework\TestCase;

final class InvoiceMassAssignmentBoundaryTest extends TestCase
{
    public function test_invoice_is_totally_guarded_and_owner_service_writes_explicitly(): void
    {
        $invoice = new Invoice;
        $service = file_get_contents(dirname(__DIR__).'/Services/InvoiceCreationService.php');

        self::assertSame(['*'], $invoice->getGuarded());
        self::assertFalse($invoice->isFillable('status'));
        self::assertIsString($service);
        self::assertTrue(
            str_contains($service, '$invoice = new Invoice();') || str_contains($service, '$invoice = new Invoice;'),
            'Failed asserting that service instantiates Invoice directly.',
        );
        self::assertStringContainsString('$invoice->forceFill([', $service);
        self::assertStringNotContainsString('Invoice::query()->create([', $service);
    }
}
