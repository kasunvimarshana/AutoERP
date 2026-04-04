<?php
use Illuminate\Support\Facades\Route;
use Modules\GS1\Infrastructure\Http\Controllers\GS1BarcodeController;
use Modules\GS1\Infrastructure\Http\Controllers\GS1LabelController;

Route::prefix('gs1')->group(function () {
    Route::get('/barcodes',      [GS1BarcodeController::class, 'index']);
    Route::post('/barcodes',     [GS1BarcodeController::class, 'store']);
    Route::get('/barcodes/{id}', [GS1BarcodeController::class, 'show']);

    Route::get('/labels',        [GS1LabelController::class, 'index']);
    Route::get('/labels/{id}',   [GS1LabelController::class, 'show']);
    Route::post('/labels',       [GS1LabelController::class, 'generateLabel']);
});
