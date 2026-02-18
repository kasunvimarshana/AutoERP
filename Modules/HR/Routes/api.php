<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\Api\AttendanceController;
use Modules\HR\Http\Controllers\Api\DepartmentController;
use Modules\HR\Http\Controllers\Api\EmployeeController;
use Modules\HR\Http\Controllers\Api\LeaveController;
use Modules\HR\Http\Controllers\Api\LeaveTypeController;
use Modules\HR\Http\Controllers\Api\PayrollController;
use Modules\HR\Http\Controllers\Api\PerformanceReviewController;
use Modules\HR\Http\Controllers\Api\PositionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/hr')->middleware('api')->group(function () {
    // Employee routes
    Route::apiResource('employees', EmployeeController::class);
    Route::get('employees/search', [EmployeeController::class, 'search']);
    Route::get('employees/department/{departmentId}', [EmployeeController::class, 'getByDepartment']);
    Route::post('employees/{id}/terminate', [EmployeeController::class, 'terminate']);

    // Department routes
    Route::apiResource('departments', DepartmentController::class);
    Route::get('departments/tree', [DepartmentController::class, 'tree']);
    Route::get('departments/{id}/employees', [DepartmentController::class, 'employees']);

    // Position routes
    Route::apiResource('positions', PositionController::class);
    Route::get('positions/{id}/employees', [PositionController::class, 'employees']);

    // Attendance routes
    Route::apiResource('attendances', AttendanceController::class);
    Route::post('attendances/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendances/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('attendances/employee/{employeeId}', [AttendanceController::class, 'getByEmployee']);

    // Leave routes
    Route::apiResource('leaves', LeaveController::class);
    Route::post('leaves/{id}/approve', [LeaveController::class, 'approve']);
    Route::post('leaves/{id}/reject', [LeaveController::class, 'reject']);
    Route::get('leaves/balance', [LeaveController::class, 'getBalance']);
    Route::get('leaves/employee/{employeeId}', [LeaveController::class, 'getByEmployee']);

    // Leave Type routes
    Route::apiResource('leave-types', LeaveTypeController::class);
    Route::get('leave-types/list', [LeaveTypeController::class, 'list']);

    // Payroll routes
    Route::apiResource('payrolls', PayrollController::class);
    Route::post('payrolls/calculate', [PayrollController::class, 'calculate']);
    Route::post('payrolls/{id}/process-payment', [PayrollController::class, 'processPayment']);
    Route::get('payrolls/{id}/payslip', [PayrollController::class, 'generatePayslip']);
    Route::get('payrolls/employee/{employeeId}', [PayrollController::class, 'getByEmployee']);

    // Performance Review routes
    Route::apiResource('performance-reviews', PerformanceReviewController::class);
    Route::get('performance-reviews/employee/{employeeId}', [PerformanceReviewController::class, 'getByEmployee']);
});
