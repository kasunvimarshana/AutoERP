<?php
use Illuminate\Support\Facades\Route;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListItemController;
use Modules\Pricing\Infrastructure\Http\Controllers\TaxRateController;
use Modules\Pricing\Infrastructure\Http\Controllers\TaxGroupController;

Route::prefix('price-lists')->group(function () {
    Route::get('/', [PriceListController::class, 'index']);
    Route::post('/', [PriceListController::class, 'store']);
    Route::get('/{id}', [PriceListController::class, 'show']);
    Route::patch('/{id}', [PriceListController::class, 'update']);
    Route::delete('/{id}', [PriceListController::class, 'destroy']);
    Route::get('/{id}/items', [PriceListItemController::class, 'index']);
    Route::post('/{id}/items', [PriceListItemController::class, 'store']);
});
Route::prefix('price-list-items')->group(function () {
    Route::get('/{id}', [PriceListItemController::class, 'show']);
    Route::patch('/{id}', [PriceListItemController::class, 'update']);
    Route::delete('/{id}', [PriceListItemController::class, 'destroy']);
});
Route::prefix('tax-rates')->group(function () {
    Route::get('/', [TaxRateController::class, 'index']);
    Route::post('/', [TaxRateController::class, 'store']);
    Route::get('/{id}', [TaxRateController::class, 'show']);
    Route::patch('/{id}', [TaxRateController::class, 'update']);
    Route::delete('/{id}', [TaxRateController::class, 'destroy']);
});
Route::prefix('tax-groups')->group(function () {
    Route::get('/', [TaxGroupController::class, 'index']);
    Route::post('/', [TaxGroupController::class, 'store']);
    Route::get('/{id}', [TaxGroupController::class, 'show']);
    Route::patch('/{id}', [TaxGroupController::class, 'update']);
    Route::delete('/{id}', [TaxGroupController::class, 'destroy']);
});
