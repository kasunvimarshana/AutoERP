<?php
use Illuminate\Support\Facades\Route;
use Modules\CRM\Presentation\Controllers\LeadController;
use Modules\CRM\Presentation\Controllers\OpportunityController;
use Modules\CRM\Presentation\Controllers\ContactController;
use Modules\CRM\Presentation\Controllers\AccountController;
use Modules\CRM\Presentation\Controllers\ActivityController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('crm/leads', LeadController::class);
    Route::post('crm/leads/{id}/convert', [LeadController::class, 'convert']);
    Route::apiResource('crm/opportunities', OpportunityController::class);
    Route::patch('crm/opportunities/{id}/stage', [OpportunityController::class, 'changeStage']);
    Route::apiResource('crm/contacts', ContactController::class);
    Route::apiResource('crm/accounts', AccountController::class);
    Route::apiResource('crm/activities', ActivityController::class);
    Route::post('crm/activities/{id}/complete', [ActivityController::class, 'complete']);
});
