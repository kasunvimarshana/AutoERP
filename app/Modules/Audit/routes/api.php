<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Infrastructure\Http\Controllers\AuditLogController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show']);
    Route::get('audit-logs/entity/{type}/{id}', [AuditLogController::class, 'forEntity']);
});
