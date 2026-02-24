<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Presentation\Controllers\AttendanceController;
use Modules\HR\Presentation\Controllers\DepartmentController;
use Modules\HR\Presentation\Controllers\EmployeeController;
use Modules\HR\Presentation\Controllers\PayrollRunController;
use Modules\HR\Presentation\Controllers\PerformanceGoalController;
use Modules\HR\Presentation\Controllers\SalaryComponentController;
use Modules\HR\Presentation\Controllers\SalaryStructureController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('hr/departments', DepartmentController::class);
    Route::apiResource('hr/employees', EmployeeController::class);
    Route::apiResource('hr/payroll-runs', PayrollRunController::class);
    Route::post('hr/payroll-runs/{id}/process', [PayrollRunController::class, 'process']);
    Route::get('hr/attendance', [AttendanceController::class, 'index']);
    Route::post('hr/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('hr/attendance/{id}/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('hr/attendance/{id}', [AttendanceController::class, 'show']);
    Route::apiResource('hr/performance-goals', PerformanceGoalController::class);
    Route::post('hr/performance-goals/{id}/complete', [PerformanceGoalController::class, 'complete']);
    Route::apiResource('hr/salary-components', SalaryComponentController::class);
    Route::get('hr/salary-structures', [SalaryStructureController::class, 'index']);
    Route::post('hr/salary-structures', [SalaryStructureController::class, 'store']);
    Route::get('hr/salary-structures/assignments', [SalaryStructureController::class, 'assignments']);
    Route::get('hr/salary-structures/{id}', [SalaryStructureController::class, 'show']);
    Route::delete('hr/salary-structures/{id}', [SalaryStructureController::class, 'destroy']);
    Route::post('hr/salary-structures/{id}/assign', [SalaryStructureController::class, 'assign']);
});
