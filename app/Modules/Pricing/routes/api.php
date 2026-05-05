<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Infrastructure\Http\Controllers\CustomerPriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListItemController;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceResolutionController;
use Modules\Pricing\Infrastructure\Http\Controllers\SupplierPriceListController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('pricing/price-lists', [PriceListController::class, 'index']);
    Route::post('pricing/price-lists', [PriceListController::class, 'store']);
    Route::get('pricing/price-lists/{priceList}', [PriceListController::class, 'show']);
    Route::put('pricing/price-lists/{priceList}', [PriceListController::class, 'update']);
    Route::delete('pricing/price-lists/{priceList}', [PriceListController::class, 'destroy']);

    Route::get('pricing/price-lists/{priceList}/items', [PriceListItemController::class, 'index']);
    Route::post('pricing/price-lists/{priceList}/items', [PriceListItemController::class, 'store']);
    Route::put('pricing/price-lists/{priceList}/items/{priceListItem}', [PriceListItemController::class, 'update']);
    Route::delete('pricing/price-lists/{priceList}/items/{priceListItem}', [PriceListItemController::class, 'destroy']);

    Route::get('pricing/customers/{customer}/price-lists', [CustomerPriceListController::class, 'index']);
    Route::post('pricing/customers/{customer}/price-lists', [CustomerPriceListController::class, 'store']);
    Route::delete('pricing/customers/{customer}/price-lists/{assignment}', [CustomerPriceListController::class, 'destroy']);

    Route::get('pricing/suppliers/{supplier}/price-lists', [SupplierPriceListController::class, 'index']);
    Route::post('pricing/suppliers/{supplier}/price-lists', [SupplierPriceListController::class, 'store']);
    Route::delete('pricing/suppliers/{supplier}/price-lists/{assignment}', [SupplierPriceListController::class, 'destroy']);

    Route::post('pricing/resolve', [PriceResolutionController::class, 'resolve']);
});
