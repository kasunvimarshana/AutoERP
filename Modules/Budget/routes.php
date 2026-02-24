<?php

use Illuminate\Support\Facades\Route;
use Modules\Budget\Presentation\Controllers\BudgetController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('budget/budgets', [BudgetController::class, 'index']);
    Route::post('budget/budgets', [BudgetController::class, 'store']);
    Route::get('budget/budgets/{id}', [BudgetController::class, 'show']);
    Route::put('budget/budgets/{id}', [BudgetController::class, 'update']);
    Route::delete('budget/budgets/{id}', [BudgetController::class, 'destroy']);
    Route::post('budget/budgets/{id}/approve', [BudgetController::class, 'approve']);
    Route::post('budget/budgets/{id}/close', [BudgetController::class, 'close']);
    Route::get('budget/budgets/{id}/variance', [BudgetController::class, 'varianceReport']);
    Route::post('budget/budgets/{id}/lines/{lineId}/actual-spend', [BudgetController::class, 'recordActualSpend']);
});
