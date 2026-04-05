<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Infrastructure\Http\Controllers\AttendanceController;
use Modules\HR\Infrastructure\Http\Controllers\DepartmentController;
use Modules\HR\Infrastructure\Http\Controllers\EmployeeController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveTypeController;
use Modules\HR\Infrastructure\Http\Controllers\PayrollController;
use Modules\HR\Infrastructure\Http\Controllers\PositionController;

Route::prefix('api')->group(function () {

    // Departments
    Route::apiResource('hr/departments', DepartmentController::class);

    // Positions
    Route::apiResource('hr/positions', PositionController::class);
    Route::get('hr/departments/{departmentId}/positions', [PositionController::class, 'byDepartment']);

    // Employees
    Route::apiResource('hr/employees', EmployeeController::class);
    Route::post('hr/employees/{id}/terminate', [EmployeeController::class, 'terminate']);
    Route::get('hr/departments/{departmentId}/employees', [EmployeeController::class, 'byDepartment']);

    // Leave Types
    Route::apiResource('hr/leave-types', LeaveTypeController::class);

    // Leave Requests
    Route::apiResource('hr/leave-requests', LeaveController::class)->except(['update']);
    Route::post('hr/leave-requests/{id}/approve', [LeaveController::class, 'approve']);
    Route::post('hr/leave-requests/{id}/reject', [LeaveController::class, 'reject']);
    Route::post('hr/leave-requests/{id}/cancel', [LeaveController::class, 'cancel']);
    Route::get('hr/employees/{employeeId}/leave-requests', [LeaveController::class, 'byEmployee']);
    Route::get('hr/leave-requests/pending', [LeaveController::class, 'pending']);

    // Attendance
    Route::apiResource('hr/attendance', AttendanceController::class);
    Route::post('hr/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('hr/attendance/{id}/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('hr/attendance/biometric-check-in', [AttendanceController::class, 'biometricCheckIn']);
    Route::get('hr/employees/{employeeId}/attendance', [AttendanceController::class, 'byEmployee']);

    // Payroll
    Route::get('hr/payroll', [PayrollController::class, 'index']);
    Route::get('hr/payroll/{id}', [PayrollController::class, 'show']);
    Route::delete('hr/payroll/{id}', [PayrollController::class, 'destroy']);
    Route::post('hr/payroll/process', [PayrollController::class, 'process']);
    Route::post('hr/payroll/{id}/approve', [PayrollController::class, 'approve']);
    Route::post('hr/payroll/{id}/mark-as-paid', [PayrollController::class, 'markAsPaid']);
    Route::post('hr/payroll/{id}/cancel', [PayrollController::class, 'cancel']);
    Route::get('hr/employees/{employeeId}/payroll', [PayrollController::class, 'byEmployee']);
    Route::get('hr/payroll/by-period', [PayrollController::class, 'byPeriod']);
});
