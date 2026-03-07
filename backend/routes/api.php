<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;

Route::get('/health', [HealthCheckController::class, 'index']);
Route::get('/ping', [HealthCheckController::class, 'ping']);

// Module routes are loaded by ModuleServiceProvider
