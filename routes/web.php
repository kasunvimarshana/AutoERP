<?php

use Illuminate\Support\Facades\Route;

// Health check endpoint (for tests and monitoring)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});

// Serve the Vue.js SPA for all routes (except API and health check)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
