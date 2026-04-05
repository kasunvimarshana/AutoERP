<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('crm')->group(function () {
        Route::apiResource('contacts',     \Modules\CRM\Infrastructure\Http\Controllers\ContactController::class);
        Route::apiResource('leads',        \Modules\CRM\Infrastructure\Http\Controllers\LeadController::class);
        Route::post('leads/{id}/qualify',      [\Modules\CRM\Infrastructure\Http\Controllers\LeadController::class, 'qualify']);
        Route::post('leads/{id}/disqualify',   [\Modules\CRM\Infrastructure\Http\Controllers\LeadController::class, 'disqualify']);
        Route::post('leads/{id}/convert',      [\Modules\CRM\Infrastructure\Http\Controllers\LeadController::class, 'convert']);
        Route::apiResource('opportunities', \Modules\CRM\Infrastructure\Http\Controllers\OpportunityController::class);
        Route::post('opportunities/{id}/close-won',  [\Modules\CRM\Infrastructure\Http\Controllers\OpportunityController::class, 'closeWon']);
        Route::post('opportunities/{id}/close-lost', [\Modules\CRM\Infrastructure\Http\Controllers\OpportunityController::class, 'closeLost']);
        Route::get('opportunities/pipeline',         [\Modules\CRM\Infrastructure\Http\Controllers\OpportunityController::class, 'pipeline']);
        Route::apiResource('activities',   \Modules\CRM\Infrastructure\Http\Controllers\ActivityController::class);
        Route::post('activities/{id}/complete', [\Modules\CRM\Infrastructure\Http\Controllers\ActivityController::class, 'complete']);
        Route::post('activities/{id}/cancel',   [\Modules\CRM\Infrastructure\Http\Controllers\ActivityController::class, 'cancel']);
    });
});
