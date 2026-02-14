<?php

use App\Modules\CRM\Http\Controllers\CustomerController;
use App\Modules\CRM\Http\Controllers\CustomerEnhancedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * @OA\Info(
 *     title="AutoERP API",
 *     version="1.0.0",
 *     description="AutoERP REST API",
 *
 *     @OA\Contact(
 *         email="support@autoerp.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

// Public routes
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // CRM Module - Original implementation
    Route::prefix('customers')->group(function () {
        Route::get('/search', [CustomerController::class, 'search']);
        Route::apiResource('/', CustomerController::class)->parameters(['' => 'id']);
    });

    // CRM Module - Enhanced CRUD Framework implementation
    Route::apiResource('customers-enhanced', CustomerEnhancedController::class);

    // Inventory Module (to be implemented)
    // Route::prefix('inventory')->group(function () {
    //     Route::apiResource('products', ProductController::class);
    //     Route::post('stock/in', [StockController::class, 'recordIncoming']);
    //     Route::post('stock/out', [StockController::class, 'recordOutgoing']);
    // });

    // Tenant Management (to be implemented)
    // Route::prefix('tenants')->group(function () {
    //     Route::apiResource('/', TenantController::class);
    //     Route::apiResource('organizations', OrganizationController::class);
    //     Route::apiResource('branches', BranchController::class);
    // });
});
