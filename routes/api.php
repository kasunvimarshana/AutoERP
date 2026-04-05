<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', [\Modules\Core\Infrastructure\Http\Controllers\HealthController::class, 'check']);
