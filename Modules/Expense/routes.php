<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Presentation\Controllers\ExpenseCategoryController;
use Modules\Expense\Presentation\Controllers\ExpenseClaimController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('expense/categories', ExpenseCategoryController::class);
    Route::apiResource('expense/claims', ExpenseClaimController::class)->except(['update']);
    Route::post('expense/claims/{id}/submit', [ExpenseClaimController::class, 'submit']);
    Route::post('expense/claims/{id}/approve', [ExpenseClaimController::class, 'approve']);
    Route::post('expense/claims/{id}/reimburse', [ExpenseClaimController::class, 'reimburse']);
});
