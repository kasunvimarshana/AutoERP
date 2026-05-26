<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceController;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceEngineController;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceReferenceController;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceLineController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/invoice')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('invoice.')
    ->group(function (): void {
        Route::post('invoices/{invoice}/engines/recalculate', [InvoiceEngineController::class, 'recalculateTotals'])
            ->name('invoices.engines.recalculate');
        Route::post('invoices/{invoice}/engines/status', [InvoiceEngineController::class, 'transitionStatus'])
            ->name('invoices.engines.status');
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('invoice-references', InvoiceReferenceController::class);
        Route::apiResource('invoice-lines', InvoiceLineController::class);
    });
