<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Presentation\Http\Controllers\JournalEntryController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');

Route::prefix('api/finance')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('finance.')
    ->group(function (): void {
        Route::apiResource('journal-entries', JournalEntryController::class)
            ->only(['index', 'show'])
            ->parameters(['journal-entries' => 'journalEntry']);
    });
