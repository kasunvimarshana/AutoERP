<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organisation\Interfaces\Http\Controllers\OrganisationController;

Route::prefix('api/v1')->group(function (): void {
    Route::get('/organisations', [OrganisationController::class, 'index']);
    Route::post('/organisations', [OrganisationController::class, 'store']);
    Route::get('/organisations/{id}', [OrganisationController::class, 'show']);
    Route::put('/organisations/{id}', [OrganisationController::class, 'update']);
    Route::delete('/organisations/{id}', [OrganisationController::class, 'destroy']);
    Route::get('/organisations/{id}/children', [OrganisationController::class, 'children']);
});
