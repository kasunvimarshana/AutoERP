<?php
use Illuminate\Support\Facades\Route;
use Modules\GS1\Infrastructure\Http\Controllers\Gs1LabelController;
Route::prefix('api')->group(function () {
    Route::apiResource('gs1-labels', Gs1LabelController::class);
});
