<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Infrastructure\Http\Controllers\LocationController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    // Static routes must be declared BEFORE the resource wildcard route to
    // prevent the {location} segment from swallowing them.
    Route::get('locations/tree', [LocationController::class, 'tree']);

    Route::apiResource('locations', LocationController::class);

    // Hierarchical read routes — all logic delegated to FindLocationServiceInterface
    Route::get('locations/{location}/descendants', [LocationController::class, 'descendants']);
    Route::get('locations/{location}/ancestors',   [LocationController::class, 'ancestors']);

    // Move to a different parent
    Route::patch('locations/{location}/move', [LocationController::class, 'move']);
});
