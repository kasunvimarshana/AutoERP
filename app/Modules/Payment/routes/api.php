<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\Controllers\PaymentController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');

Route::prefix('api/payment')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('payment.')
    ->group(function (): void {
        Route::get('lookups/{type}', [PaymentController::class, 'lookup'])->name('lookups');
        Route::post('advances/{advance}/allocations', [PaymentController::class, 'allocateAdvance'])->name('advances.allocate');
        Route::post('payments/{payment}/allocations', [PaymentController::class, 'allocate'])->name('payments.allocate');
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show', 'destroy']);
    });
