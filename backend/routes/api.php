<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// API Documentation endpoints
Route::prefix('documentation')->group(function () {
    Route::get('/', [\App\Http\Controllers\ApiDocumentationController::class, 'ui'])->name('api.docs.ui');
    Route::get('/json', [\App\Http\Controllers\ApiDocumentationController::class, 'json'])->name('api.docs.json');
    Route::get('/markdown', [\App\Http\Controllers\ApiDocumentationController::class, 'markdown'])->name('api.docs.markdown');
    Route::get('/export/{format}', [\App\Http\Controllers\ApiDocumentationController::class, 'export'])->name('api.docs.export');
});

// Module metadata API (for frontend dynamic configuration)
Route::prefix('modules')->group(function () {
    Route::get('/', [\App\Http\Controllers\ModuleController::class, 'index']);
    Route::get('/routes', [\App\Http\Controllers\ModuleController::class, 'routes']);
    Route::get('/permissions', [\App\Http\Controllers\ModuleController::class, 'permissions']);
    Route::get('/{moduleId}', [\App\Http\Controllers\ModuleController::class, 'show']);
});
