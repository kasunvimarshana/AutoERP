<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollAdjustmentController;
use Modules\Payroll\Http\Controllers\PayrollAssignmentController;
use Modules\Payroll\Http\Controllers\PayrollComponentController;
use Modules\Payroll\Http\Controllers\PayrollLoanController;
use Modules\Payroll\Http\Controllers\PayrollPayRunController;
use Modules\Payroll\Http\Controllers\PayrollPayslipController;
use Modules\Payroll\Http\Controllers\PayrollPeriodController;
use Modules\Payroll\Http\Controllers\PayrollStatutoryRuleController;
use Modules\Payroll\Http\Controllers\PayrollStructureController;
use Modules\Payroll\Http\Controllers\PayrollTaxTableController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
];

Route::prefix('api/v1/payroll')->middleware($middleware)->group(function (): void {
    // Salary Components
    Route::get('components', [PayrollComponentController::class, 'index']);
    Route::post('components', [PayrollComponentController::class, 'store']);
    Route::get('components/{id}', [PayrollComponentController::class, 'show']);
    Route::put('components/{id}', [PayrollComponentController::class, 'update']);
    Route::delete('components/{id}', [PayrollComponentController::class, 'destroy']);

    // Salary Structures
    Route::get('structures', [PayrollStructureController::class, 'index']);
    Route::post('structures', [PayrollStructureController::class, 'store']);
    Route::get('structures/{id}', [PayrollStructureController::class, 'show']);
    Route::put('structures/{id}', [PayrollStructureController::class, 'update']);
    Route::delete('structures/{id}', [PayrollStructureController::class, 'destroy']);

    // Employee Assignments
    Route::get('assignments', [PayrollAssignmentController::class, 'index']);
    Route::post('assignments', [PayrollAssignmentController::class, 'store']);
    Route::get('assignments/{id}', [PayrollAssignmentController::class, 'show']);
    Route::put('assignments/{id}', [PayrollAssignmentController::class, 'update']);

    // Tax Tables
    Route::get('tax-tables', [PayrollTaxTableController::class, 'index']);
    Route::post('tax-tables', [PayrollTaxTableController::class, 'store']);
    Route::get('tax-tables/{id}', [PayrollTaxTableController::class, 'show']);
    Route::put('tax-tables/{id}', [PayrollTaxTableController::class, 'update']);

    // Statutory Rules
    Route::get('statutory-rules', [PayrollStatutoryRuleController::class, 'index']);
    Route::post('statutory-rules', [PayrollStatutoryRuleController::class, 'store']);
    Route::put('statutory-rules/{id}', [PayrollStatutoryRuleController::class, 'update']);

    // Pay Periods
    Route::get('periods', [PayrollPeriodController::class, 'index']);
    Route::post('periods', [PayrollPeriodController::class, 'store']);
    Route::get('periods/{id}', [PayrollPeriodController::class, 'show']);

    // Pay Runs
    Route::get('pay-runs', [PayrollPayRunController::class, 'index']);
    Route::post('pay-runs', [PayrollPayRunController::class, 'store']);
    Route::get('pay-runs/{id}', [PayrollPayRunController::class, 'show']);
    Route::post('pay-runs/{id}/calculate', [PayrollPayRunController::class, 'calculate']);
    Route::post('pay-runs/{id}/approve', [PayrollPayRunController::class, 'approve']);
    Route::post('pay-runs/{id}/pay', [PayrollPayRunController::class, 'pay']);
    Route::post('pay-runs/{id}/reverse', [PayrollPayRunController::class, 'reverse']);

    // Payslips
    Route::get('payslips', [PayrollPayslipController::class, 'index']);
    Route::get('payslips/{id}', [PayrollPayslipController::class, 'show']);

    // Adjustments
    Route::get('adjustments', [PayrollAdjustmentController::class, 'index']);
    Route::post('adjustments', [PayrollAdjustmentController::class, 'store']);
    Route::post('adjustments/calculate-retro-arrears', [PayrollAdjustmentController::class, 'calculateRetroArrears']);
    Route::patch('adjustments/{id}/cancel', [PayrollAdjustmentController::class, 'cancel']);

    // Loans and Advances
    Route::get('loans', [PayrollLoanController::class, 'index']);
    Route::post('loans', [PayrollLoanController::class, 'store']);
    Route::get('loans/{id}', [PayrollLoanController::class, 'show']);
    Route::post('loans/{id}/approve', [PayrollLoanController::class, 'approve']);
    Route::post('loans/{id}/disburse', [PayrollLoanController::class, 'disburse']);
    Route::post('loans/{id}/cancel', [PayrollLoanController::class, 'cancel']);
});
