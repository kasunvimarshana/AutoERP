<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\Controllers\PaymentMethodController;
use Modules\Payment\Presentation\Http\Controllers\PaymentGroupController;
use Modules\Payment\Presentation\Http\Controllers\PaymentController;
use Modules\Payment\Presentation\Http\Controllers\PaymentAllocationController;
use Modules\Payment\Presentation\Http\Controllers\CashRegisterController;
use Modules\Payment\Presentation\Http\Controllers\CheckController;
use Modules\Payment\Presentation\Http\Controllers\AdvancePaymentController;
use Modules\Payment\Presentation\Http\Controllers\AdvancePaymentAllocationController;
use Modules\Payment\Presentation\Http\Controllers\WriteOffController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/payment')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('payment.')
    ->group(function (): void {
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::apiResource('payment-groups', PaymentGroupController::class);
        Route::apiResource('payments', PaymentController::class);
        Route::apiResource('payment-allocations', PaymentAllocationController::class);
        Route::apiResource('cash-registers', CashRegisterController::class);
        Route::apiResource('checks', CheckController::class);
        Route::apiResource('advance-payments', AdvancePaymentController::class);
        Route::apiResource('advance-payment-allocations', AdvancePaymentAllocationController::class);
        Route::apiResource('write-offs', WriteOffController::class);
    });