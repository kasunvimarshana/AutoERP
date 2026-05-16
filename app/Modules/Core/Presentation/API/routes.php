<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Presentation\API\Controllers\HealthController;

Route::get('/health', HealthController::class)->name('core.health');
