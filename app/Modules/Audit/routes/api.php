<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Infrastructure\Http\Controllers\AuditLogController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{auditLogId}', [AuditLogController::class, 'show']);
});
