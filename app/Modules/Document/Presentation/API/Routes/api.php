<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Document\Presentation\API\Controllers\DocumentController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/document')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('document.')
    ->group(function (): void {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::patch('documents/{document}/status', [DocumentController::class, 'changeStatus'])
            ->name('documents.change-status');
        Route::post('documents/{document}/attachments', [DocumentController::class, 'uploadAttachment'])
            ->name('documents.attachments.store');

        Route::get('documents/{document}/comments', [DocumentController::class, 'listComments'])
            ->name('documents.comments.index');
        Route::post('documents/{document}/comments', [DocumentController::class, 'addComment'])
            ->name('documents.comments.store');

        Route::get('documents/{document}/activities', [DocumentController::class, 'listActivities'])
            ->name('documents.activities.index');
        Route::post('documents/{document}/activities', [DocumentController::class, 'addActivity'])
            ->name('documents.activities.store');

        Route::get('documents/{document}/events', [DocumentController::class, 'listEvents'])
            ->name('documents.events.index');
        Route::post('documents/{document}/events', [DocumentController::class, 'addEvent'])
            ->name('documents.events.store');

        Route::get('documents/{document}/permissions', [DocumentController::class, 'listPermissions'])
            ->name('documents.permissions.index');
        Route::post('documents/{document}/permissions', [DocumentController::class, 'addPermission'])
            ->name('documents.permissions.store');

        Route::get('documents/{document}/relations', [DocumentController::class, 'listRelations'])
            ->name('documents.relations.index');
        Route::post('documents/{document}/relations', [DocumentController::class, 'addRelation'])
            ->name('documents.relations.store');

        Route::get('types', [DocumentController::class, 'listDocumentTypes'])->name('types.index');
        Route::post('types', [DocumentController::class, 'createDocumentType'])->name('types.store');

        Route::get('item-types', [DocumentController::class, 'listItemTypes'])->name('item-types.index');
        Route::post('item-types', [DocumentController::class, 'createItemType'])->name('item-types.store');

        Route::get('definitions', [DocumentController::class, 'listDocumentDefinitions'])
            ->name('definitions.index');
        Route::post('definitions', [DocumentController::class, 'createDocumentDefinition'])
            ->name('definitions.store');

        Route::get('item-definitions', [DocumentController::class, 'listItemDefinitions'])
            ->name('item-definitions.index');
        Route::post('item-definitions', [DocumentController::class, 'createItemDefinition'])
            ->name('item-definitions.store');
    });
