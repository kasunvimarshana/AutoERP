<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Infrastructure\Http\Controllers\HealthController;

// API routes are loaded by each module's ServiceProvider

// Health check (no auth required – suitable for liveness/readiness probes)
Route::get('/health', [HealthController::class, 'check']);
