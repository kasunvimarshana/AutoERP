<?php
use Illuminate\Support\Facades\Route;
use Modules\Audit\Presentation\Controllers\AuditController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('audit-logs', [AuditController::class, 'index']);
    Route::get('audit-logs/{id}', [AuditController::class, 'show']);
});
