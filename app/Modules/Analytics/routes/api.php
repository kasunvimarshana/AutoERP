<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Infrastructure\Http\Controllers\AnalyticsController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('analytics/summary', [AnalyticsController::class, 'summary']);
    Route::get('analytics/snapshots', [AnalyticsController::class, 'index']);
    Route::post('analytics/snapshots', [AnalyticsController::class, 'store']);
    Route::get('analytics/snapshots/{id}', [AnalyticsController::class, 'show']);
    Route::delete('analytics/snapshots/{id}', [AnalyticsController::class, 'destroy']);
});
