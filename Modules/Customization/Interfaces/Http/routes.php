<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customization\Interfaces\Http\Controllers\CustomFieldController;
use Modules\Customization\Interfaces\Http\Controllers\CustomFieldValueController;

Route::prefix('api/v1')->group(function (): void {
    Route::get('/custom-fields', [CustomFieldController::class, 'index']);
    Route::post('/custom-fields', [CustomFieldController::class, 'store']);
    Route::get('/custom-fields/{id}', [CustomFieldController::class, 'show']);
    Route::put('/custom-fields/{id}', [CustomFieldController::class, 'update']);
    Route::delete('/custom-fields/{id}', [CustomFieldController::class, 'destroy']);

    Route::get('/custom-field-values', [CustomFieldValueController::class, 'index']);
    Route::post('/custom-field-values', [CustomFieldValueController::class, 'store']);
});
