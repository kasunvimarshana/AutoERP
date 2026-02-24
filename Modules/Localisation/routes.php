<?php

use Illuminate\Support\Facades\Route;
use Modules\Localisation\Presentation\Controllers\LanguagePackController;
use Modules\Localisation\Presentation\Controllers\LocalePreferenceController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('localisation/language-packs', [LanguagePackController::class, 'index']);
    Route::post('localisation/language-packs', [LanguagePackController::class, 'store']);
    Route::get('localisation/language-packs/{id}', [LanguagePackController::class, 'show']);
    Route::put('localisation/language-packs/{id}', [LanguagePackController::class, 'update']);
    Route::delete('localisation/language-packs/{id}', [LanguagePackController::class, 'destroy']);

    Route::get('localisation/preferences', [LocalePreferenceController::class, 'show']);
    Route::put('localisation/preferences', [LocalePreferenceController::class, 'update']);
});
