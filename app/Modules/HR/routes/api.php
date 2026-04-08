<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Infrastructure\Http\Controllers\AttendanceController;
use Modules\HR\Infrastructure\Http\Controllers\BiometricAttendanceController;
use Modules\HR\Infrastructure\Http\Controllers\DepartmentController;
use Modules\HR\Infrastructure\Http\Controllers\EmployeeController;
use Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveRequestController;
use Modules\HR\Infrastructure\Http\Controllers\PayrollController;
use Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController;
use Modules\HR\Infrastructure\Http\Controllers\PositionController;
use Modules\HR\Infrastructure\Http\Controllers\TrainingController;

Route::middleware(['auth:api', 'resolve.tenant'])->prefix('hr')->group(function () {
    // Static routes must be declared BEFORE resource wildcard routes
    Route::get('employees/by-department/{departmentId}', [EmployeeController::class, 'byDepartment']);
    Route::post('employees/{id}/link-user', [EmployeeController::class, 'linkUser']);
    Route::apiResource('employees', EmployeeController::class);

    Route::get('departments/tree', [DepartmentController::class, 'tree']);
    Route::apiResource('departments', DepartmentController::class);

    Route::get('positions/by-department/{departmentId}', [PositionController::class, 'byDepartment']);
    Route::apiResource('positions', PositionController::class);

    Route::get('leave-requests/by-employee/{employeeId}', [LeaveRequestController::class, 'byEmployee']);
    Route::post('leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave-requests/{id}/reject',  [LeaveRequestController::class, 'reject']);
    Route::post('leave-requests/{id}/cancel',  [LeaveRequestController::class, 'cancel']);
    Route::apiResource('leave-requests', LeaveRequestController::class);

    Route::get('attendance/employee/{employeeId}', [AttendanceController::class, 'byEmployee']);
    Route::apiResource('attendance', AttendanceController::class);

    // Biometric device routes – static paths before apiResource wildcards
    Route::get('biometric/devices',              [BiometricAttendanceController::class, 'devices']);
    Route::post('biometric/check-in',            [BiometricAttendanceController::class, 'checkIn']);
    Route::post('biometric/check-out',           [BiometricAttendanceController::class, 'checkOut']);
    Route::post('employees/{id}/biometric/enroll', [BiometricAttendanceController::class, 'enroll']);

    Route::prefix('me')->group(function () {
        Route::get('profile',        [EmployeeSelfServiceController::class, 'profile']);
        Route::get('leave-requests', [EmployeeSelfServiceController::class, 'leaveRequests']);
        Route::post('leave-requests', [EmployeeSelfServiceController::class, 'submitLeaveRequest']);
        Route::post('leave-requests/{id}/cancel', [EmployeeSelfServiceController::class, 'cancelLeaveRequest']);
        Route::get('attendance',     [EmployeeSelfServiceController::class, 'attendance']);
    });

    // Payroll
    Route::get('payroll/employee/{employeeId}', [PayrollController::class, 'byEmployee']);
    Route::post('payroll/{id}/process', [PayrollController::class, 'process']);
    Route::apiResource('payroll', PayrollController::class);

    // Performance Reviews
    Route::get('performance-reviews/employee/{employeeId}', [PerformanceReviewController::class, 'byEmployee']);
    Route::post('performance-reviews/{id}/submit', [PerformanceReviewController::class, 'submit']);
    Route::apiResource('performance-reviews', PerformanceReviewController::class);

    // Training
    Route::get('training/by-status/{status}', [TrainingController::class, 'byStatus']);
    Route::apiResource('training', TrainingController::class);
});
