<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Asset\Infrastructure\Http\Controllers\AssetAvailabilityController;

Route::prefix('assets')
    ->middleware(['auth.configured', 'resolve.tenant'])
    ->group(static function (): void {
        Route::get('{asset}/availability', [AssetAvailabilityController::class, 'show'])
            ->name('assets.availability.show');

        Route::post('availability/sync', [AssetAvailabilityController::class, 'sync'])
            ->name('assets.availability.sync');
    });
