<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Routing\Route;
use Modules\Invoice\Constants\InvoicePermission;
use Tests\TestCase;

final class InvoicePrintRouteAuthorizationTest extends TestCase
{
    public function test_authenticated_print_routes_require_invoice_view_permission(): void
    {
        $permission = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission')
            .':'.InvoicePermission::VIEW;

        foreach (['invoice.print', 'invoice.pdf', 'invoices.print', 'invoices.pdf'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains($permission, $route->gatherMiddleware(), $routeName.' must require invoice view permission.');
        }
    }

    public function test_signed_public_invoice_routes_remain_signed_share_surfaces(): void
    {
        foreach (['invoices.public.print', 'invoices.public.pdf', 'invoices.public.shared-pdf'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains('signed', $route->gatherMiddleware());
        }
    }
}
