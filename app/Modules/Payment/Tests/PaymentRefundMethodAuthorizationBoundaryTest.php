<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use PHPUnit\Framework\TestCase;

final class PaymentRefundMethodAuthorizationBoundaryTest extends TestCase
{
    public function test_refund_method_lookup_requires_refund_permission_at_route_and_controller_boundaries(): void
    {
        $routes = (string) file_get_contents(__DIR__.'/../Routes/api.php');
        $controller = (string) file_get_contents(__DIR__.'/../Http/Controllers/PaymentMethodController.php');

        self::assertStringContainsString(
            "Route::get('refund-methods', [PaymentMethodController::class, 'usableForRefund'])",
            $routes,
        );
        self::assertStringContainsString(
            "->middleware(\$requires(PaymentPermission::PAYMENTS_REFUND))",
            $routes,
        );
        self::assertStringContainsString(
            'public function usableForRefund(ListPaymentMethodRequest $request)',
            $controller,
        );
        self::assertStringContainsString(
            'PaymentPermission::PAYMENTS_REFUND',
            $controller,
        );
    }
}
