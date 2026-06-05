<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');

Route::prefix('api/invoice')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('invoice.')
    ->group(function (): void {
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::apiResource('invoices', InvoiceController::class);
    });
