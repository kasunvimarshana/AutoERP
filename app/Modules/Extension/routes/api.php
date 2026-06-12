<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Extension\Http\Controllers\AttachmentController;
use Modules\Extension\Http\Controllers\CommentController;
use Modules\Extension\Http\Controllers\EntityAttributeController;

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
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('extension.')
    ->group(function (): void {
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
            ->whereNumber('attachment')
            ->name('attachments.download');
        Route::get('attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
            ->whereNumber('attachment')
            ->name('attachments.preview');
        Route::get('attachments/{attachment}/versions', [AttachmentController::class, 'versions'])
            ->whereNumber('attachment')
            ->name('attachments.versions');
        Route::post('attachments/{attachment}/versions', [AttachmentController::class, 'storeVersion'])
            ->whereNumber('attachment')
            ->name('attachments.versions.store');
        Route::apiResource('attachments', AttachmentController::class);
        Route::apiResource('entity-attributes', EntityAttributeController::class);
        Route::apiResource('comments', CommentController::class);
    });
