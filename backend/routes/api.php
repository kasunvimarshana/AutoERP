<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CRM\CustomerController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Inventory\StockController;
use App\Http\Controllers\Api\V1\MasterData\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });

        // Inventory Management
        Route::prefix('inventory')->group(function () {
            
            // Products
            Route::prefix('products')->group(function () {
                Route::get('/', [ProductController::class, 'index']);
                Route::post('/', [ProductController::class, 'store']);
                Route::get('search', [ProductController::class, 'search']);
                Route::get('low-stock', [ProductController::class, 'lowStock']);
                Route::get('needing-reorder', [ProductController::class, 'needingReorder']);
                Route::post('bulk-import', [ProductController::class, 'bulkImport']);
                Route::get('{id}', [ProductController::class, 'show']);
                Route::put('{id}', [ProductController::class, 'update']);
                Route::delete('{id}', [ProductController::class, 'destroy']);
                Route::post('{id}/toggle-status', [ProductController::class, 'toggleStatus']);
            });

            // Stock Management
            Route::prefix('stock')->group(function () {
                Route::get('balance', [StockController::class, 'balance']);
                Route::post('add', [StockController::class, 'addStock']);
                Route::post('remove', [StockController::class, 'removeStock']);
                Route::post('transfer', [StockController::class, 'transferStock']);
                Route::post('adjust', [StockController::class, 'adjustStock']);
                Route::get('available-batches', [StockController::class, 'availableBatches']);
                Route::post('pick', [StockController::class, 'pickStock']);
                Route::get('movement-history', [StockController::class, 'movementHistory']);
            });
        });

        // Master Data Management
        Route::prefix('master-data')->group(function () {
            // Units
            Route::prefix('units')->group(function () {
                Route::get('/', [UnitController::class, 'index']);
                Route::post('/', [UnitController::class, 'store']);
                Route::get('{id}', [UnitController::class, 'show']);
                Route::put('{id}', [UnitController::class, 'update']);
                Route::delete('{id}', [UnitController::class, 'destroy']);
                Route::post('convert', [UnitController::class, 'convert']);
            });
        });

        // CRM Management
        Route::prefix('crm')->group(function () {
            // Customers
            Route::prefix('customers')->group(function () {
                Route::get('/', [CustomerController::class, 'index']);
                Route::post('/', [CustomerController::class, 'store']);
                Route::get('{id}', [CustomerController::class, 'show']);
                Route::put('{id}', [CustomerController::class, 'update']);
                Route::delete('{id}', [CustomerController::class, 'destroy']);
                Route::post('{id}/check-credit', [CustomerController::class, 'checkCreditLimit']);
            });
        });

        // Future modules will be added here:
        // - Pricing
        // - IAM (Users, Roles, Permissions)
        // - Organizations & Branches
        // - CRM
        // - Procurement
        // - POS
        // - Invoicing
        // - Payments
        // - Manufacturing
        // - Warehouse
        // - Reporting
        // - Analytics
    });
});
