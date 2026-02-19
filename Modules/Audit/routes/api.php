<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| Audit API Routes
|--------------------------------------------------------------------------
|
| Read-only API endpoints for audit log access, statistics, and export.
| All routes are protected by authentication and policy-based authorization.
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {
    // Audit log endpoints
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        // Statistics must come before {auditLog} to avoid route conflict
        Route::get('statistics', [AuditLogController::class, 'statistics'])
            ->name('statistics');

        Route::get('export', [AuditLogController::class, 'export'])
            ->name('export');

        Route::get('/', [AuditLogController::class, 'index'])
            ->name('index');

        Route::get('{auditLog}', [AuditLogController::class, 'show'])
            ->name('show');
    });
});
