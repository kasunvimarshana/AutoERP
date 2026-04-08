<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceRuleController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('price-lists', PriceListController::class);
    Route::apiResource('price-rules', PriceRuleController::class);
});
