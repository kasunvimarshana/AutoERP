<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('audit-logs')->group(function () {
        Route::get('/',                   [\Modules\Audit\Infrastructure\Http\Controllers\AuditLogController::class, 'index']);
        Route::get('/{id}',               [\Modules\Audit\Infrastructure\Http\Controllers\AuditLogController::class, 'show']);
        Route::get('/entity/{type}/{id}', [\Modules\Audit\Infrastructure\Http\Controllers\AuditLogController::class, 'byEntity']);
    });
});
