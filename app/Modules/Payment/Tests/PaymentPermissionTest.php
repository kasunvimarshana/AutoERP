<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Modules\Payment\Constants\PaymentPermission;
use PHPUnit\Framework\TestCase;

final class PaymentPermissionTest extends TestCase
{
    public function test_permission_catalogue_uses_the_canonical_payment_action_names(): void
    {
        $permissions = array_keys(PaymentPermission::descriptions());

        self::assertSame([
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.submit',
            'payments.approve',
            'payments.post',
            'payments.void',
            'payments.reverse',
            'payments.allocate',
            'payments.refund',
            'payments.settle',
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'cheque-templates.view',
            'cheque-templates.create',
            'cheque-templates.update',
            'cheque-templates.delete',
            'cheques.preview',
            'cheques.print',
        ], $permissions);
        self::assertSame($permissions, array_values(array_unique($permissions)));

        foreach (PaymentPermission::descriptions() as $permission => $description) {
            self::assertMatchesRegularExpression('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/', $permission);
            self::assertNotSame('', trim($description));
        }
    }
}
