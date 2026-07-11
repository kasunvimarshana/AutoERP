<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Modules\Payment\Models\Payment;
use PHPUnit\Framework\TestCase;

final class PaymentMassAssignmentBoundaryTest extends TestCase
{
    public function test_payment_is_totally_guarded_and_owner_service_writes_explicitly(): void
    {
        $payment = new Payment();
        $service = file_get_contents(dirname(__DIR__).'/Services/PaymentCreationService.php');

        self::assertSame(['*'], $payment->getGuarded());
        self::assertFalse($payment->isFillable('document_status'));
        self::assertIsString($service);
        self::assertStringContainsString('$payment = new Payment();', $service);
        self::assertStringContainsString('$payment->forceFill([', $service);
        self::assertStringNotContainsString('Payment::query()->create([', $service);
    }
}
