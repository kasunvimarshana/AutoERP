<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Extension\Http\Controllers\AttachmentController;
use Modules\Extension\Http\Controllers\EntityAttributeController;
use Modules\Extension\Http\Controllers\CommentController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/extension')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('extension.')
    ->group(function (): void {
        Route::apiResource('attachments', AttachmentController::class);
        Route::apiResource('entity-attributes', EntityAttributeController::class);
        Route::apiResource('comments', CommentController::class);
    });