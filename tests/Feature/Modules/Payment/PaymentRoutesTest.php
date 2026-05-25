<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Payment;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PaymentRoutesTest extends TestCase
{
    public function testPaymentRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('payment.payment-methods.index'));
        self::assertTrue(Route::has('payment.payment-methods.store'));
        self::assertTrue(Route::has('payment.payment-methods.show'));
        self::assertTrue(Route::has('payment.payment-methods.update'));
        self::assertTrue(Route::has('payment.payment-methods.destroy'));
        self::assertTrue(Route::has('payment.payment-groups.index'));
        self::assertTrue(Route::has('payment.payment-groups.store'));
        self::assertTrue(Route::has('payment.payment-groups.show'));
        self::assertTrue(Route::has('payment.payment-groups.update'));
        self::assertTrue(Route::has('payment.payment-groups.destroy'));
        self::assertTrue(Route::has('payment.payments.index'));
        self::assertTrue(Route::has('payment.payments.store'));
        self::assertTrue(Route::has('payment.payments.show'));
        self::assertTrue(Route::has('payment.payments.update'));
        self::assertTrue(Route::has('payment.payments.destroy'));
        self::assertTrue(Route::has('payment.payment-allocations.index'));
        self::assertTrue(Route::has('payment.payment-allocations.store'));
        self::assertTrue(Route::has('payment.payment-allocations.show'));
        self::assertTrue(Route::has('payment.payment-allocations.update'));
        self::assertTrue(Route::has('payment.payment-allocations.destroy'));
        self::assertTrue(Route::has('payment.cash-registers.index'));
        self::assertTrue(Route::has('payment.cash-registers.store'));
        self::assertTrue(Route::has('payment.cash-registers.show'));
        self::assertTrue(Route::has('payment.cash-registers.update'));
        self::assertTrue(Route::has('payment.cash-registers.destroy'));
        self::assertTrue(Route::has('payment.checks.index'));
        self::assertTrue(Route::has('payment.checks.store'));
        self::assertTrue(Route::has('payment.checks.show'));
        self::assertTrue(Route::has('payment.checks.update'));
        self::assertTrue(Route::has('payment.checks.destroy'));
        self::assertTrue(Route::has('payment.advance-payments.index'));
        self::assertTrue(Route::has('payment.advance-payments.store'));
        self::assertTrue(Route::has('payment.advance-payments.show'));
        self::assertTrue(Route::has('payment.advance-payments.update'));
        self::assertTrue(Route::has('payment.advance-payments.destroy'));
        self::assertTrue(Route::has('payment.advance-payment-allocations.index'));
        self::assertTrue(Route::has('payment.advance-payment-allocations.store'));
        self::assertTrue(Route::has('payment.advance-payment-allocations.show'));
        self::assertTrue(Route::has('payment.advance-payment-allocations.update'));
        self::assertTrue(Route::has('payment.advance-payment-allocations.destroy'));
        self::assertTrue(Route::has('payment.write-offs.index'));
        self::assertTrue(Route::has('payment.write-offs.store'));
        self::assertTrue(Route::has('payment.write-offs.show'));
        self::assertTrue(Route::has('payment.write-offs.update'));
        self::assertTrue(Route::has('payment.write-offs.destroy'));
    }

    public function testPaymentRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('payment.payment-methods.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}