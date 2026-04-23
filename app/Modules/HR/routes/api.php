<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\HR\Infrastructure\Http\Controllers\AttendanceLogController;
use Modules\HR\Infrastructure\Http\Controllers\AttendanceRecordController;
use Modules\HR\Infrastructure\Http\Controllers\BiometricDeviceController;
use Modules\HR\Infrastructure\Http\Controllers\EmployeeDocumentController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveBalanceController;
use Modules\HR\Infrastructure\Http\Controllers\LeavePolicyController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveRequestController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveTypeController;
use Modules\HR\Infrastructure\Http\Controllers\PayrollItemController;
use Modules\HR\Infrastructure\Http\Controllers\PayrollRunController;
use Modules\HR\Infrastructure\Http\Controllers\PayslipController;
use Modules\HR\Infrastructure\Http\Controllers\PerformanceCycleController;
use Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController;
use Modules\HR\Infrastructure\Http\Controllers\ShiftController;

Route::prefix('hr')->middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('shifts', ShiftController::class);
    Route::post('shifts/{shift}/assign', [ShiftController::class, 'assign']);

    Route::apiResource('leave-types', LeaveTypeController::class);

    Route::apiResource('leave-policies', LeavePolicyController::class);

    Route::get('leave-balances', [LeaveBalanceController::class, 'index']);
    Route::get('leave-balances/{leaveBalance}', [LeaveBalanceController::class, 'show']);

    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);

    Route::get('attendance-logs', [AttendanceLogController::class, 'index']);
    Route::post('attendance-logs', [AttendanceLogController::class, 'store']);
    Route::get('attendance-logs/{attendanceLog}', [AttendanceLogController::class, 'show']);

    Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
    Route::get('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);
    Route::put('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
    Route::post('attendance-records/process', [AttendanceRecordController::class, 'process']);

    Route::apiResource('biometric-devices', BiometricDeviceController::class);
    Route::post('biometric-devices/{biometricDevice}/sync', [BiometricDeviceController::class, 'sync']);

    Route::apiResource('payroll-runs', PayrollRunController::class);
    Route::post('payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve']);
    Route::post('payroll-runs/{payrollRun}/process', [PayrollRunController::class, 'process']);

    Route::apiResource('payroll-items', PayrollItemController::class);

    Route::get('payslips', [PayslipController::class, 'index']);
    Route::get('payslips/{payslip}', [PayslipController::class, 'show']);

    Route::apiResource('performance-cycles', PerformanceCycleController::class);

    Route::apiResource('performance-reviews', PerformanceReviewController::class);
    Route::post('performance-reviews/{performanceReview}/submit', [PerformanceReviewController::class, 'submit']);

    Route::apiResource('employee-documents', EmployeeDocumentController::class);
});
