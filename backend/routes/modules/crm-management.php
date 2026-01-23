<?php

use App\Modules\CRMManagement\Http\Controllers\CommunicationController;
use App\Modules\CRMManagement\Http\Controllers\NotificationController;
use App\Modules\CRMManagement\Http\Controllers\CustomerSegmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CRM Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Communication routes
    Route::apiResource('communications', CommunicationController::class);
    
    // Notification routes
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'store', 'show']);
    Route::post('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    
    // Customer Segment routes
    Route::apiResource('customer-segments', CustomerSegmentController::class);
});
