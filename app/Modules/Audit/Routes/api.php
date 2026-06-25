<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditLogController;
use Modules\Audit\Http\Controllers\Platform\PlatformAuditLogController;
use Modules\User\Constants\PlatformPermission;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/v1/audit-logs')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('audit.logs.')
    ->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('{id}', [AuditLogController::class, 'show'])->whereNumber('id')->name('show');
    });


$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');

Route::prefix('api/v1/platform/audit-logs')
    ->middleware([
        'api',
        $platformHost,
        'auth:'.$platformGuard,
        $currentUserMiddleware,
        $platformOperator,
        'platform.permission:'.PlatformPermission::AUDIT_VIEW,
    ])
    ->name('platform.audit.logs.')
    ->group(function (): void {
        Route::get('/', [PlatformAuditLogController::class, 'index'])->name('index');
        Route::get('{id}', [PlatformAuditLogController::class, 'show'])->whereNumber('id')->name('show');
    });
