<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\ContactController;
use Modules\CRM\Http\Controllers\CustomerController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\OpportunityController;

/*
|--------------------------------------------------------------------------
| CRM API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:jwt'])->group(function () {

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/{customer}/contacts', [CustomerController::class, 'contacts']);
    Route::get('customers/{customer}/opportunities', [CustomerController::class, 'opportunities']);

    // Contacts
    Route::apiResource('contacts', ContactController::class);

    // Leads
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign']);

    // Opportunities
    Route::get('opportunities/pipeline/stats', [OpportunityController::class, 'pipelineStats']);
    Route::apiResource('opportunities', OpportunityController::class);
    Route::post('opportunities/{opportunity}/advance', [OpportunityController::class, 'advance']);
    Route::post('opportunities/{opportunity}/win', [OpportunityController::class, 'win']);
    Route::post('opportunities/{opportunity}/lose', [OpportunityController::class, 'lose']);
});
