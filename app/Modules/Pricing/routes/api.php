<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListItemController;

Route::apiResource('price-lists', PriceListController::class);
Route::post('price-lists/{id}/activate', [PriceListController::class, 'activate']);
Route::post('price-lists/{id}/deactivate', [PriceListController::class, 'deactivate']);

Route::apiResource('price-list-items', PriceListItemController::class);
