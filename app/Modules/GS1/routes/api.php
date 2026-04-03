<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\GS1\Infrastructure\Http\Controllers\Gs1BarcodeController;
use Modules\GS1\Infrastructure\Http\Controllers\Gs1IdentifierController;

Route::prefix('gs1')->group(function () {
    Route::apiResource('identifiers', Gs1IdentifierController::class);
    Route::apiResource('barcodes', Gs1BarcodeController::class);
});
