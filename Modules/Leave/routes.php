<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Presentation\Controllers\LeaveAllocationController;
use Modules\Leave\Presentation\Controllers\LeaveRequestController;
use Modules\Leave\Presentation\Controllers\LeaveTypeController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('leave/types', LeaveTypeController::class);
    Route::apiResource('leave/requests', LeaveRequestController::class)->except(['update']);
    Route::post('leave/requests/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave/requests/{id}/reject', [LeaveRequestController::class, 'reject']);
    Route::apiResource('leave/allocations', LeaveAllocationController::class)->except(['update']);
    Route::post('leave/allocations/{id}/approve', [LeaveAllocationController::class, 'approve']);
});
