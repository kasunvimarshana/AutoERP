<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Core module.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'module' => 'core',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});
