<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Crm\Interfaces\Http\Controllers\ActivityController;
use Modules\Crm\Interfaces\Http\Controllers\ContactController;
use Modules\Crm\Interfaces\Http\Controllers\LeadController;

Route::prefix('api/v1')->group(function (): void {
    // Contacts
    Route::get('/crm/contacts', [ContactController::class, 'index']);
    Route::post('/crm/contacts', [ContactController::class, 'store']);
    Route::get('/crm/contacts/{id}', [ContactController::class, 'show']);
    Route::put('/crm/contacts/{id}', [ContactController::class, 'update']);
    Route::delete('/crm/contacts/{id}', [ContactController::class, 'destroy']);
    Route::get('/crm/contacts/{id}/leads', [ContactController::class, 'leads']);
    Route::get('/crm/contacts/{id}/activities', [ContactController::class, 'activities']);

    // Leads
    Route::get('/crm/leads', [LeadController::class, 'index']);
    Route::post('/crm/leads', [LeadController::class, 'store']);
    Route::get('/crm/leads/{id}', [LeadController::class, 'show']);
    Route::put('/crm/leads/{id}', [LeadController::class, 'update']);
    Route::delete('/crm/leads/{id}', [LeadController::class, 'destroy']);
    Route::get('/crm/leads/{id}/activities', [LeadController::class, 'activities']);

    // Activities
    Route::get('/crm/activities', [ActivityController::class, 'index']);
    Route::post('/crm/activities', [ActivityController::class, 'store']);
    Route::get('/crm/activities/{id}', [ActivityController::class, 'show']);
    Route::delete('/crm/activities/{id}', [ActivityController::class, 'destroy']);
});
