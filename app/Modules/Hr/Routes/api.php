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
use Modules\Core\Tenancy\TenantFeature;
use Modules\Hr\Http\Controllers\EmployeeController;
use Modules\Hr\Http\Controllers\EmployeeRelationController;
use Modules\Hr\Http\Controllers\HrAttendanceController;
use Modules\Hr\Http\Controllers\HrCertificationController;
use Modules\Hr\Http\Controllers\HrDepartmentController;
use Modules\Hr\Http\Controllers\HrDesignationController;
use Modules\Hr\Http\Controllers\HrEmploymentTypeController;
use Modules\Hr\Http\Controllers\HrLeaveController;
use Modules\Hr\Http\Controllers\HrLicenseController;
use Modules\Hr\Http\Controllers\HrShiftController;
use Modules\Hr\Http\Controllers\HrSkillController;
use Modules\Hr\Services\HrAuthorizationService;

$featureMiddleware = (string) config('tenant.entitlements.middleware_alias', 'tenant.feature');
$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    $featureMiddleware.':'.TenantFeature::HR,
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/hr')->middleware($middleware)->name('api.v1.hr.')->group(function () use ($requires): void {
    Route::get('employees/lookup/{kind?}', [EmployeeController::class, 'lookup'])
        ->whereIn('kind', ['active', 'available', 'service-available', 'by-skill', 'by-designation'])
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.lookup');
    Route::post('employees/with-relations', [EmployeeController::class, 'storeWithRelations'])
        ->middleware($requires(HrAuthorizationService::CREATE_EMPLOYEES))
        ->name('employees.with-relations.store');
    Route::patch('employees/{employee}/activate', [EmployeeController::class, 'activate'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.activate');
    Route::patch('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.deactivate');
    Route::patch('employees/{employee}/status', [EmployeeController::class, 'changeStatus'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.status');
    Route::patch('employees/{employee}/availability', [EmployeeController::class, 'changeAvailability'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.availability.update');

    $relations = [
        'contacts' => ['storeContact', 'updateContact', 'destroyContact', 'contact'],
        'addresses' => ['storeAddress', 'updateAddress', 'destroyAddress', 'address'],
        'documents' => ['storeDocument', 'updateDocument', 'destroyDocument', 'document'],
        'skills' => ['storeSkill', 'updateSkill', 'destroySkill', 'assignment'],
        'certifications' => ['storeCertification', 'updateCertification', 'destroyCertification', 'assignment'],
        'licenses' => ['storeLicense', 'updateLicense', 'destroyLicense', 'assignment'],
    ];

    foreach ($relations as $relation => [$store, $update, $destroy, $child]) {
        $routeName = "employees.{$relation}";

        Route::get("employees/{employee}/{$relation}", [EmployeeRelationController::class, $relation])
            ->whereNumber('employee')
            ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
            ->name("{$routeName}.index");
        Route::post("employees/{employee}/{$relation}", [EmployeeRelationController::class, $store])
            ->whereNumber('employee')
            ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
            ->name("{$routeName}.store");
        Route::put("employees/{employee}/{$relation}/{{$child}}", [EmployeeRelationController::class, $update])
            ->whereNumber(['employee', $child])
            ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
            ->name("{$routeName}.update");
        Route::delete("employees/{employee}/{$relation}/{{$child}}", [EmployeeRelationController::class, $destroy])
            ->whereNumber(['employee', $child])
            ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
            ->name("{$routeName}.destroy");
    }

    Route::get('employees/{employee}/rates', [EmployeeRelationController::class, 'rates'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.rates.index');
    Route::post('employees/{employee}/rates', [EmployeeRelationController::class, 'storeRate'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.rates.store');
    Route::get('employees/{employee}/availability', [EmployeeRelationController::class, 'availability'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.availability.show');
    Route::post('employees/{employee}/availability', [EmployeeRelationController::class, 'storeAvailability'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.availability.store');
    Route::get('employees/{employee}/status-history', [EmployeeRelationController::class, 'statusHistory'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.status-history.index');

    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.index');
    Route::post('employees', [EmployeeController::class, 'store'])
        ->middleware($requires(HrAuthorizationService::CREATE_EMPLOYEES))
        ->name('employees.store');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.show');
    Route::match(['put', 'patch'], 'employees/{employee}', [EmployeeController::class, 'update'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.update');
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::DELETE_EMPLOYEES))
        ->name('employees.destroy');

    foreach ([
        'departments' => HrDepartmentController::class,
        'designations' => HrDesignationController::class,
        'employment-types' => HrEmploymentTypeController::class,
        'skills' => HrSkillController::class,
        'certifications' => HrCertificationController::class,
        'licenses' => HrLicenseController::class,
    ] as $path => $controller) {
        Route::get("{$path}/lookup", [$controller, 'lookup'])
            ->middleware($requires(HrAuthorizationService::VIEW_MASTER_DATA))
            ->name("{$path}.lookup");
        Route::get($path, [$controller, 'index'])
            ->middleware($requires(HrAuthorizationService::VIEW_MASTER_DATA))
            ->name("{$path}.index");
        Route::post($path, [$controller, 'store'])
            ->middleware($requires(HrAuthorizationService::MANAGE_MASTER_DATA))
            ->name("{$path}.store");
        Route::get("{$path}/{id}", [$controller, 'show'])
            ->whereNumber('id')
            ->middleware($requires(HrAuthorizationService::VIEW_MASTER_DATA))
            ->name("{$path}.show");
        Route::match(['put', 'patch'], "{$path}/{id}", [$controller, 'update'])
            ->whereNumber('id')
            ->middleware($requires(HrAuthorizationService::MANAGE_MASTER_DATA))
            ->name("{$path}.update");
        Route::delete("{$path}/{id}", [$controller, 'destroy'])
            ->whereNumber('id')
            ->middleware($requires(HrAuthorizationService::MANAGE_MASTER_DATA))
            ->name("{$path}.destroy");
    }

    // Shifts, Rosters & Swaps
    Route::get('shifts', [HrShiftController::class, 'index'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shifts.index');
    Route::post('shifts', [HrShiftController::class, 'store'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shifts.store');
    Route::get('shifts/{shift}', [HrShiftController::class, 'show'])
        ->whereNumber('shift')
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shifts.show');
    Route::match(['put', 'patch'], 'shifts/{shift}', [HrShiftController::class, 'update'])
        ->whereNumber('shift')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shifts.update');
    Route::delete('shifts/{shift}', [HrShiftController::class, 'destroy'])
        ->whereNumber('shift')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shifts.destroy');

    Route::get('roster', [HrShiftController::class, 'roster'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('roster.index');
    Route::post('shift-assignments', [HrShiftController::class, 'assign'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-assignments.store');
    Route::delete('shift-assignments/{assignment}', [HrShiftController::class, 'cancelAssignment'])
        ->whereNumber('assignment')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-assignments.destroy');

    Route::post('shift-swaps', [HrShiftController::class, 'requestSwap'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-swaps.store');
    Route::patch('shift-swaps/{swap}/peer-action', [HrShiftController::class, 'peerActionSwap'])
        ->whereNumber('swap')
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-swaps.peer-action');
    Route::patch('shift-swaps/{swap}/manager-action', [HrShiftController::class, 'managerActionSwap'])
        ->whereNumber('swap')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-swaps.manager-action');
    Route::post('shift-reallocations', [HrShiftController::class, 'reallocateCover'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-reallocations.store');

    // Attendance & Punches & Regularizations
    Route::post('attendance/punch', [HrAttendanceController::class, 'punch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.punch');
    Route::get('attendance/today/{employee}', [HrAttendanceController::class, 'today'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_ATTENDANCE))
        ->name('attendance.today');
    Route::get('attendance/records', [HrAttendanceController::class, 'records'])
        ->middleware($requires(HrAuthorizationService::VIEW_ATTENDANCE))
        ->name('attendance.records');
    Route::post('attendance/regularize', [HrAttendanceController::class, 'requestRegularization'])
        ->middleware($requires(HrAuthorizationService::REGULARIZE_ATTENDANCE))
        ->name('attendance.regularize');
    Route::get('attendance/regularizations', [HrAttendanceController::class, 'listRegularizations'])
        ->middleware($requires(HrAuthorizationService::REGULARIZE_ATTENDANCE))
        ->name('attendance.regularizations.index');
    Route::patch('attendance/regularizations/{regularization}/action', [HrAttendanceController::class, 'actionRegularization'])
        ->whereNumber('regularization')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.regularizations.action');
    Route::post('attendance/records/{record}/direct-adjust', [HrAttendanceController::class, 'directAdjust'])
        ->whereNumber('record')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.records.direct-adjust');
    Route::post('attendance/lock', [HrAttendanceController::class, 'lock'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.lock');

    // Leave Types, Policies, Balances, Requests & Comp-Off
    Route::get('leave-types', [HrLeaveController::class, 'leaveTypes'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-types.index');
    Route::post('leave-types', [HrLeaveController::class, 'storeLeaveType'])
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-types.store');
    Route::get('leave-policies', [HrLeaveController::class, 'leavePolicies'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-policies.index');
    Route::post('leave-policies', [HrLeaveController::class, 'storeLeavePolicy'])
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-policies.store');
    Route::get('leave-balances', [HrLeaveController::class, 'balances'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-balances.index');
    Route::get('leave-requests', [HrLeaveController::class, 'requests'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-requests.index');
    Route::post('leave-requests', [HrLeaveController::class, 'apply'])
        ->middleware($requires(HrAuthorizationService::APPLY_LEAVES))
        ->name('leave-requests.apply');
    Route::patch('leave-requests/{leaveRequest}/action', [HrLeaveController::class, 'action'])
        ->whereNumber('leaveRequest')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-requests.action');
    Route::post('leave-requests/{leaveRequest}/cancel', [HrLeaveController::class, 'cancel'])
        ->whereNumber('leaveRequest')
        ->middleware($requires(HrAuthorizationService::APPLY_LEAVES))
        ->name('leave-requests.cancel');
    Route::post('comp-off/claim', [HrLeaveController::class, 'claimCompOff'])
        ->middleware($requires(HrAuthorizationService::APPLY_LEAVES))
        ->name('comp-off.claim');
    Route::patch('comp-off/claims/{claim}/action', [HrLeaveController::class, 'actionCompOffClaim'])
        ->whereNumber('claim')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('comp-off.action');
});
