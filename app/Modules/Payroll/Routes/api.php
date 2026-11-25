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
use Modules\Payroll\Services\PayrollAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/payroll')->middleware($middleware)->name('api.v1.payroll.')->group(function () use ($requires): void {
    // Salary Components
    Route::get('components', [PayrollComponentController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_COMPONENTS));
    Route::post('components', [PayrollComponentController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_COMPONENTS));
    Route::get('components/{id}', [PayrollComponentController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_COMPONENTS));
    Route::put('components/{id}', [PayrollComponentController::class, 'update'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_COMPONENTS));
    Route::delete('components/{id}', [PayrollComponentController::class, 'destroy'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_COMPONENTS));

    // Salary Structures
    Route::get('structures', [PayrollStructureController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_STRUCTURES));
    Route::post('structures', [PayrollStructureController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_STRUCTURES));
    Route::get('structures/{id}', [PayrollStructureController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_STRUCTURES));
    Route::put('structures/{id}', [PayrollStructureController::class, 'update'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_STRUCTURES));
    Route::delete('structures/{id}', [PayrollStructureController::class, 'destroy'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_STRUCTURES));

    // Employee Assignments
    Route::get('assignments', [PayrollAssignmentController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::ASSIGN_SALARY));
    Route::post('assignments', [PayrollAssignmentController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::ASSIGN_SALARY));
    Route::get('assignments/{id}', [PayrollAssignmentController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::ASSIGN_SALARY));
    Route::put('assignments/{id}', [PayrollAssignmentController::class, 'update'])
        ->middleware($requires(PayrollAuthorizationService::ASSIGN_SALARY));

    // Tax Tables
    Route::get('tax-tables', [PayrollTaxTableController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_TAX_TABLES));
    Route::post('tax-tables', [PayrollTaxTableController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_TAX_TABLES));
    Route::get('tax-tables/{id}', [PayrollTaxTableController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_TAX_TABLES));
    Route::put('tax-tables/{id}', [PayrollTaxTableController::class, 'update'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_TAX_TABLES));

    // Statutory Rules
    Route::get('statutory-rules', [PayrollStatutoryRuleController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_STATUTORY_RULES));
    Route::post('statutory-rules', [PayrollStatutoryRuleController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_STATUTORY_RULES));
    Route::put('statutory-rules/{id}', [PayrollStatutoryRuleController::class, 'update'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_STATUTORY_RULES));

    // Pay Periods
    Route::get('periods', [PayrollPeriodController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_PERIODS));
    Route::post('periods', [PayrollPeriodController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_PERIODS));
    Route::get('periods/{id}', [PayrollPeriodController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_PERIODS));

    // Pay Runs
    Route::get('pay-runs', [PayrollPayRunController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL));
    Route::post('pay-runs', [PayrollPayRunController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::CALCULATE_PAY_RUN));
    Route::get('pay-runs/{id}', [PayrollPayRunController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL));
    Route::post('pay-runs/{id}/calculate', [PayrollPayRunController::class, 'calculate'])
        ->middleware($requires(PayrollAuthorizationService::CALCULATE_PAY_RUN));
    Route::post('pay-runs/{id}/approve', [PayrollPayRunController::class, 'approve'])
        ->middleware($requires(PayrollAuthorizationService::APPROVE_PAY_RUN));
    Route::post('pay-runs/{id}/pay', [PayrollPayRunController::class, 'pay'])
        ->middleware($requires(PayrollAuthorizationService::PAY_PAY_RUN));
    Route::post('pay-runs/{id}/reverse', [PayrollPayRunController::class, 'reverse'])
        ->middleware($requires(PayrollAuthorizationService::REVERSE_PAY_RUN));

    // Payslips
    Route::get('payslips', [PayrollPayslipController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYSLIPS.','.PayrollAuthorizationService::VIEW_PAYROLL));
    Route::get('payslips/{id}', [PayrollPayslipController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYSLIPS.','.PayrollAuthorizationService::VIEW_PAYROLL));

    // Adjustments
    Route::get('adjustments', [PayrollAdjustmentController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_ADJUSTMENTS));
    Route::post('adjustments', [PayrollAdjustmentController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_ADJUSTMENTS));
    Route::post('adjustments/calculate-retro-arrears', [PayrollAdjustmentController::class, 'calculateRetroArrears'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_ADJUSTMENTS));
    Route::patch('adjustments/{id}/cancel', [PayrollAdjustmentController::class, 'cancel'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_ADJUSTMENTS));

    // Loans and Advances
    Route::get('loans', [PayrollLoanController::class, 'index'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_LOANS));
    Route::post('loans', [PayrollLoanController::class, 'store'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_LOANS));
    Route::get('loans/{id}', [PayrollLoanController::class, 'show'])
        ->middleware($requires(PayrollAuthorizationService::VIEW_PAYROLL.','.PayrollAuthorizationService::MANAGE_LOANS));
    Route::post('loans/{id}/approve', [PayrollLoanController::class, 'approve'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_LOANS));
    Route::post('loans/{id}/disburse', [PayrollLoanController::class, 'disburse'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_LOANS));
    Route::post('loans/{id}/cancel', [PayrollLoanController::class, 'cancel'])
        ->middleware($requires(PayrollAuthorizationService::MANAGE_LOANS));
});
