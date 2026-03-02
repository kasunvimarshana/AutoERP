<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CRM\Interfaces\Http\Controllers\CRMController;

/*
|--------------------------------------------------------------------------
| CRM Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('crm.')->group(function (): void {
    Route::get('crm/leads', [CRMController::class, 'listLeads'])->name('leads.index');
    Route::get('crm/leads/{id}', [CRMController::class, 'showLead'])->name('leads.show');
    Route::post('crm/leads', [CRMController::class, 'createLead'])->name('leads.store');
    Route::delete('crm/leads/{id}', [CRMController::class, 'deleteLead'])->name('leads.destroy');
    Route::post('crm/leads/{id}/convert', [CRMController::class, 'convertLeadToOpportunity'])->name('leads.convert');
    Route::get('crm/opportunities', [CRMController::class, 'listOpportunities'])->name('opportunities.index');
    Route::get('crm/opportunities/{id}', [CRMController::class, 'showOpportunity'])->name('opportunities.show');
    Route::post('crm/opportunities/{id}/stage', [CRMController::class, 'updateOpportunityStage'])->name('opportunities.stage');
    Route::post('crm/opportunities/{id}/close-won', [CRMController::class, 'closeWon'])->name('opportunities.close-won');
    Route::post('crm/opportunities/{id}/close-lost', [CRMController::class, 'closeLost'])->name('opportunities.close-lost');
    Route::get('crm/customers', [CRMController::class, 'listCustomers'])->name('customers.index');
});
