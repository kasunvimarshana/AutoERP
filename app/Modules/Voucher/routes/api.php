<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Voucher\Presentation\Http\Controllers\VoucherController;
use Modules\Voucher\Presentation\Http\Controllers\RecurringVoucherController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/voucher')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('voucher.')
    ->group(function (): void {
        Route::apiResource('vouchers', VoucherController::class);
        Route::apiResource('recurring-vouchers', RecurringVoucherController::class);
    });