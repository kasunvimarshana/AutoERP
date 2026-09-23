<?php

declare(strict_types=1);

namespace Tests\Feature\Purchase;

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Tests\TestCase;

final class PurchaseBatchNumberPermissionBoundaryTest extends TestCase
{
    public function test_goods_receipt_batch_number_route_requires_create_permission(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.purchase.goods-receipts.batch-number');

        self::assertNotNull($route);
        self::assertContains('POST', $route->methods());
        self::assertContains(
            (string) config('user.tenant.permission_middleware_alias', 'tenant.permission')
                .':'.PurchaseAuthorizationService::GOODS_RECEIPTS_CREATE,
            $route->gatherMiddleware(),
        );
    }
}
