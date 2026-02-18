<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuditLogController;
use Modules\Core\Http\Controllers\ConfigurationController;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\HealthController;
use Modules\Core\Http\Controllers\MetadataController;
use Modules\Core\Http\Controllers\NotificationController;
use Modules\Core\Http\Controllers\TenantController;

// Health check endpoint (public)
Route::get('/health', [HealthController::class, 'index'])->name('core.health');

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Dashboard endpoints (requires tenant context)
    Route::middleware(['tenant'])->prefix('dashboard')->name('core.dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
        Route::get('/revenue-overview', [DashboardController::class, 'revenueOverview'])->name('revenue-overview');
        Route::get('/sales-by-category', [DashboardController::class, 'salesByCategory'])->name('sales-by-category');
    });

    // Tenant management (admin only)
    Route::middleware(['role:admin'])->prefix('tenants')->name('core.tenants.')->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('/{uuid}', [TenantController::class, 'show'])->name('show');
        Route::put('/{uuid}', [TenantController::class, 'update'])->name('update');
        Route::delete('/{uuid}', [TenantController::class, 'destroy'])->name('destroy');
        Route::post('/{uuid}/suspend', [TenantController::class, 'suspend'])->name('suspend');
        Route::post('/{uuid}/activate', [TenantController::class, 'activate'])->name('activate');
    });

    // Configuration management (requires tenant context)
    Route::middleware(['tenant'])->prefix('configuration')->name('core.configuration.')->group(function () {
        Route::get('/', [ConfigurationController::class, 'index'])->name('index');
        Route::get('/{key}', [ConfigurationController::class, 'show'])->name('show');
        Route::post('/', [ConfigurationController::class, 'store'])->name('store');
        Route::delete('/{key}', [ConfigurationController::class, 'destroy'])->name('destroy');
    });

    // Audit logs
    Route::prefix('audit-logs')->name('core.audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
    });

    // Notifications
    Route::prefix('notifications')->name('core.notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
        Route::get('/count', [NotificationController::class, 'count'])->name('count');
        Route::get('/statistics', [NotificationController::class, 'statistics'])->name('statistics');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/read-multiple', [NotificationController::class, 'markMultipleAsRead'])->name('mark-multiple-as-read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/preferences', [NotificationController::class, 'getPreferences'])->name('preferences');
        Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('update-preferences');
    });

    // Metadata API (requires tenant context)
    Route::middleware(['tenant'])->prefix('metadata')->name('core.metadata.')->group(function () {
        Route::get('/tenant/configuration', [MetadataController::class, 'getTenantConfiguration'])->name('tenant-config');
        Route::get('/user/permissions', [MetadataController::class, 'getUserPermissions'])->name('user-permissions');
        Route::get('/modules', [MetadataController::class, 'getAllModules'])->name('modules');
        Route::get('/modules/{moduleName}', [MetadataController::class, 'getModuleMetadata'])->name('module');
        Route::get('/forms/{formId}', [MetadataController::class, 'getFormMetadata'])->name('form');
        Route::get('/tables/{tableId}', [MetadataController::class, 'getTableMetadata'])->name('table');
        Route::get('/dashboards/{dashboardId?}', [MetadataController::class, 'getDashboardMetadata'])->name('dashboard');
        Route::get('/navigation', [MetadataController::class, 'getNavigation'])->name('navigation');
        Route::get('/permissions', [MetadataController::class, 'getPermissions'])->name('permissions');
        Route::get('/workflows/{workflowId}', [MetadataController::class, 'getWorkflowMetadata'])->name('workflow');
    });
});

