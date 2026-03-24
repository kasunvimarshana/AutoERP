<?php

use Illuminate\Support\Facades\Route;

// API routes are loaded by each module's ServiceProvider

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
