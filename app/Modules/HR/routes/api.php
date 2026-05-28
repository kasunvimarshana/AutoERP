<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\HR\Presentation\Http\Controllers\DepartmentController;
use Modules\HR\Presentation\Http\Controllers\DesignationController;
use Modules\HR\Presentation\Http\Controllers\EmploymentTypeController;
use Modules\HR\Presentation\Http\Controllers\EmployeeController;
use Modules\HR\Presentation\Http\Controllers\EmployeeContactController;
use Modules\HR\Presentation\Http\Controllers\EmployeeAddressController;
use Modules\HR\Presentation\Http\Controllers\EmployeeDocumentController;
use Modules\HR\Presentation\Http\Controllers\EmployeeContractController;
use Modules\HR\Presentation\Http\Controllers\BiometricDeviceController;
use Modules\HR\Presentation\Http\Controllers\HolidayController;
use Modules\HR\Presentation\Http\Controllers\AttendanceLogController;
use Modules\HR\Presentation\Http\Controllers\ShiftController;
use Modules\HR\Presentation\Http\Controllers\ShiftAssignmentController;
use Modules\HR\Presentation\Http\Controllers\AttendanceRecordController;
use Modules\HR\Presentation\Http\Controllers\LeaveTypeController;
use Modules\HR\Presentation\Http\Controllers\LeavePolicyController;
use Modules\HR\Presentation\Http\Controllers\LeavePolicyLineController;
use Modules\HR\Presentation\Http\Controllers\LeaveAllocationController;
use Modules\HR\Presentation\Http\Controllers\LeaveApplicationController;
use Modules\HR\Presentation\Http\Controllers\SalaryComponentController;
use Modules\HR\Presentation\Http\Controllers\SalaryStructureController;
use Modules\HR\Presentation\Http\Controllers\SalaryStructureLineController;
use Modules\HR\Presentation\Http\Controllers\EmployeeSalaryAssignmentController;
use Modules\HR\Presentation\Http\Controllers\PayrollRunController;
use Modules\HR\Presentation\Http\Controllers\PayslipController;
use Modules\HR\Presentation\Http\Controllers\PayslipLineController;
use Modules\HR\Presentation\Http\Controllers\PerformanceCycleController;
use Modules\HR\Presentation\Http\Controllers\PerformanceReviewController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/hr')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('hr.')
    ->group(function (): void {
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('employment-types', EmploymentTypeController::class);

        Route::get('employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup');
        Route::get('employees/active', [EmployeeController::class, 'activeList'])->name('employees.active-list');
        Route::get('employees/by-department/{departmentId}', [EmployeeController::class, 'byDepartment'])
            ->name('employees.by-department');
        Route::get('employees/by-designation/{designationId}', [EmployeeController::class, 'byDesignation'])
            ->name('employees.by-designation');
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('employees/{employee}/status', [EmployeeController::class, 'status'])->name('employees.status');
        Route::get(
            'employees/{employee}/validate-for-vehicle-service',
            [EmployeeController::class, 'validateForVehicleService'],
        )
            ->name('employees.validate-for-vehicle-service');
        Route::get('employees/{employee}/employment-details', [EmployeeController::class, 'employmentDetails'])
            ->name('employees.employment-details.show');
        Route::put('employees/{employee}/employment-details', [EmployeeController::class, 'updateEmploymentDetails'])
            ->name('employees.employment-details.update');

        Route::get('employees/{employee}/contacts', [EmployeeContactController::class, 'index'])
            ->name('employees.contacts.index');
        Route::post('employees/{employee}/contacts', [EmployeeContactController::class, 'store'])
            ->name('employees.contacts.store');
        Route::get('employees/{employee}/contacts/{contact}', [EmployeeContactController::class, 'show'])
            ->name('employees.contacts.show');
        Route::put('employees/{employee}/contacts/{contact}', [EmployeeContactController::class, 'update'])
            ->name('employees.contacts.update');
        Route::delete('employees/{employee}/contacts/{contact}', [EmployeeContactController::class, 'destroy'])
            ->name('employees.contacts.destroy');

        Route::get('employees/{employee}/addresses', [EmployeeAddressController::class, 'index'])
            ->name('employees.addresses.index');
        Route::post('employees/{employee}/addresses', [EmployeeAddressController::class, 'store'])
            ->name('employees.addresses.store');
        Route::put('employees/{employee}/addresses/{address}', [EmployeeAddressController::class, 'update'])
            ->name('employees.addresses.update');
        Route::delete('employees/{employee}/addresses/{address}', [EmployeeAddressController::class, 'destroy'])
            ->name('employees.addresses.destroy');

        Route::get('employees/{employee}/user-accesses', [EmployeeController::class, 'listUserAccesses'])
            ->name('employees.user-accesses.index');
        Route::post('employees/{employee}/user-accesses', [EmployeeController::class, 'createUserAccess'])
            ->name('employees.user-accesses.store');
        Route::post('employees/{employee}/user-accesses/link-existing', [EmployeeController::class, 'linkExistingUser'])
            ->name('employees.user-accesses.link-existing');
        Route::patch(
            'employees/{employee}/user-accesses/{access}/deactivate',
            [EmployeeController::class, 'deactivateUserAccess'],
        )
            ->name('employees.user-accesses.deactivate');
        Route::delete('employees/{employee}/user-accesses/{access}', [EmployeeController::class, 'unlinkUserAccess'])
            ->name('employees.user-accesses.unlink');

        Route::apiResource('employee-documents', EmployeeDocumentController::class);
        Route::apiResource('employee-contracts', EmployeeContractController::class);
        Route::apiResource('biometric-devices', BiometricDeviceController::class);
        Route::apiResource('holidays', HolidayController::class);
        Route::apiResource('attendance-logs', AttendanceLogController::class);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('shift-assignments', ShiftAssignmentController::class);
        Route::apiResource('attendance-records', AttendanceRecordController::class);
        Route::apiResource('leave-types', LeaveTypeController::class);
        Route::apiResource('leave-policies', LeavePolicyController::class);
        Route::apiResource('leave-policy-lines', LeavePolicyLineController::class);
        Route::apiResource('leave-allocations', LeaveAllocationController::class);
        Route::apiResource('leave-applications', LeaveApplicationController::class);
        Route::apiResource('salary-components', SalaryComponentController::class);
        Route::apiResource('salary-structures', SalaryStructureController::class);
        Route::apiResource('salary-structure-lines', SalaryStructureLineController::class);
        Route::apiResource('employee-salary-assignments', EmployeeSalaryAssignmentController::class);
        Route::apiResource('payroll-runs', PayrollRunController::class);
        Route::apiResource('payslips', PayslipController::class);
        Route::apiResource('payslip-lines', PayslipLineController::class);
        Route::apiResource('performance-cycles', PerformanceCycleController::class);
        Route::apiResource('performance-reviews', PerformanceReviewController::class);
    });
