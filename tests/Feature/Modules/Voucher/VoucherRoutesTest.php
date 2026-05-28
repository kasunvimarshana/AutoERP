<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Voucher;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VoucherRoutesTest extends TestCase
{
    public function testVoucherRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('voucher.types.index'));
        self::assertTrue(Route::has('voucher.types.store'));
        self::assertTrue(Route::has('voucher.types.show'));
        self::assertTrue(Route::has('voucher.types.update'));
        self::assertTrue(Route::has('voucher.types.activate'));
        self::assertTrue(Route::has('voucher.types.deactivate'));

        self::assertTrue(Route::has('voucher.vouchers.index'));
        self::assertTrue(Route::has('voucher.vouchers.store'));
        self::assertTrue(Route::has('voucher.vouchers.show'));
        self::assertTrue(Route::has('voucher.vouchers.update'));
        self::assertTrue(Route::has('voucher.vouchers.destroy'));

        self::assertTrue(Route::has('voucher.vouchers.lines.upsert'));
        self::assertTrue(Route::has('voucher.vouchers.allocations.index'));
        self::assertTrue(Route::has('voucher.vouchers.allocations.store'));
        self::assertTrue(Route::has('voucher.allocations.update'));

        self::assertTrue(Route::has('voucher.vouchers.submit'));
        self::assertTrue(Route::has('voucher.vouchers.approve'));
        self::assertTrue(Route::has('voucher.vouchers.reject'));
        self::assertTrue(Route::has('voucher.vouchers.post'));
        self::assertTrue(Route::has('voucher.vouchers.cancel'));
        self::assertTrue(Route::has('voucher.vouchers.reverse'));
        self::assertTrue(Route::has('voucher.vouchers.history'));

        self::assertTrue(Route::has('voucher.utilities.preview-number'));
        self::assertTrue(Route::has('voucher.utilities.validate-balance'));
        self::assertTrue(Route::has('voucher.utilities.validate-payment-method'));
        self::assertTrue(Route::has('voucher.utilities.preview-posting'));
    }

    public function testVoucherRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('voucher.vouchers.index');

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
