<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Voucher;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VoucherRoutesTest extends TestCase
{
    public function testVoucherRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('voucher.vouchers.index'));
        self::assertTrue(Route::has('voucher.vouchers.store'));
        self::assertTrue(Route::has('voucher.vouchers.show'));
        self::assertTrue(Route::has('voucher.vouchers.update'));
        self::assertTrue(Route::has('voucher.vouchers.destroy'));
        self::assertTrue(Route::has('voucher.recurring-vouchers.index'));
        self::assertTrue(Route::has('voucher.recurring-vouchers.store'));
        self::assertTrue(Route::has('voucher.recurring-vouchers.show'));
        self::assertTrue(Route::has('voucher.recurring-vouchers.update'));
        self::assertTrue(Route::has('voucher.recurring-vouchers.destroy'));
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