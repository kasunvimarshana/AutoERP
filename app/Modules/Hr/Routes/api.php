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
use Modules\Hr\Http\Controllers\HrAttendanceAnomalyController;
use Modules\Hr\Http\Controllers\HrAttendanceCaptureController;
use Modules\Hr\Http\Controllers\HrAttendanceController;
use Modules\Hr\Http\Controllers\HrAttendanceCredentialController;
use Modules\Hr\Http\Controllers\HrAttendanceGeofenceController;
use Modules\Hr\Http\Controllers\HrBiometricDeviceController;
use Modules\Hr\Http\Controllers\HrBiometricPushReceiverController;
use Modules\Hr\Http\Controllers\HrCertificationController;
use Modules\Hr\Http\Controllers\HrDepartmentController;
use Modules\Hr\Http\Controllers\HrDesignationController;
use Modules\Hr\Http\Controllers\HrEmploymentTypeController;
use Modules\Hr\Http\Controllers\HrHolidayController;
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
    Route::get('shift-assignments', [HrShiftController::class, 'assignments'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-assignments.index');
    Route::post('shift-assignments', [HrShiftController::class, 'assign'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-assignments.store');
    Route::delete('shift-assignments/{assignment}', [HrShiftController::class, 'cancelAssignment'])
        ->whereNumber('assignment')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-assignments.destroy');

    Route::get('shift-swaps', [HrShiftController::class, 'swaps'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-swaps.index');
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
    Route::get('shift-reallocations', [HrShiftController::class, 'reallocations'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-reallocations.index');
    Route::post('shift-reallocations', [HrShiftController::class, 'reallocateCover'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-reallocations.store');

    // Shift Rotations & Rotation Assignments
    Route::get('shift-rotations', [HrShiftController::class, 'indexRotations'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-rotations.index');
    Route::post('shift-rotations', [HrShiftController::class, 'storeRotation'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotations.store');
    Route::get('shift-rotations/{rotation}', [HrShiftController::class, 'showRotation'])
        ->whereNumber('rotation')
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-rotations.show');
    Route::match(['put', 'patch'], 'shift-rotations/{rotation}', [HrShiftController::class, 'updateRotation'])
        ->whereNumber('rotation')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotations.update');
    Route::delete('shift-rotations/{rotation}', [HrShiftController::class, 'destroyRotation'])
        ->whereNumber('rotation')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotations.destroy');
    Route::post('shift-rotations/{rotation}/steps', [HrShiftController::class, 'storeRotationStep'])
        ->whereNumber('rotation')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotations.steps.store');
    Route::delete('shift-rotations/{rotation}/steps/{step}', [HrShiftController::class, 'destroyRotationStep'])
        ->whereNumber(['rotation', 'step'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotations.steps.destroy');
    Route::get('shift-rotation-assignments', [HrShiftController::class, 'rotationAssignments'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('shift-rotation-assignments.index');
    Route::post('shift-rotation-assignments', [HrShiftController::class, 'assignRotation'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotation-assignments.store');
    Route::delete('shift-rotation-assignments/{assignment}', [HrShiftController::class, 'cancelRotationAssignment'])
        ->whereNumber('assignment')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('shift-rotation-assignments.destroy');

    // Holiday Calendars & Holidays
    Route::get('holiday-calendars', [HrHolidayController::class, 'indexCalendars'])
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('holiday-calendars.index');
    Route::post('holiday-calendars', [HrHolidayController::class, 'storeCalendar'])
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holiday-calendars.store');
    Route::get('holiday-calendars/{calendar}', [HrHolidayController::class, 'showCalendar'])
        ->whereNumber('calendar')
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('holiday-calendars.show');
    Route::match(['put', 'patch'], 'holiday-calendars/{calendar}', [HrHolidayController::class, 'updateCalendar'])
        ->whereNumber('calendar')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holiday-calendars.update');
    Route::delete('holiday-calendars/{calendar}', [HrHolidayController::class, 'destroyCalendar'])
        ->whereNumber('calendar')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holiday-calendars.destroy');
    Route::get('holiday-calendars/{calendar}/holidays', [HrHolidayController::class, 'indexHolidays'])
        ->whereNumber('calendar')
        ->middleware($requires(HrAuthorizationService::VIEW_SHIFTS))
        ->name('holiday-calendars.holidays.index');
    Route::post('holiday-calendars/{calendar}/holidays', [HrHolidayController::class, 'storeHoliday'])
        ->whereNumber('calendar')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holiday-calendars.holidays.store');
    Route::match(['put', 'patch'], 'holidays/{holiday}', [HrHolidayController::class, 'updateHoliday'])
        ->whereNumber('holiday')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holidays.update');
    Route::delete('holidays/{holiday}', [HrHolidayController::class, 'destroyHoliday'])
        ->whereNumber('holiday')
        ->middleware($requires(HrAuthorizationService::MANAGE_SHIFTS))
        ->name('holidays.destroy');

    // Attendance & Punches & Regularizations
    Route::post('attendance/punch', [HrAttendanceController::class, 'punch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.punch');
    Route::post('attendance/capture', [HrAttendanceCaptureController::class, 'capture'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.unified');
    Route::post('attendance/capture/web', [HrAttendanceCaptureController::class, 'webPunch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.web');
    Route::post('attendance/capture/mobile', [HrAttendanceCaptureController::class, 'mobilePunch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.mobile');
    Route::post('attendance/capture/rfid', [HrAttendanceCaptureController::class, 'rfidPunch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.rfid');
    Route::post('attendance/capture/nfc', [HrAttendanceCaptureController::class, 'nfcPunch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.nfc');
    Route::post('attendance/capture/barcode', [HrAttendanceCaptureController::class, 'barcodePunch'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.barcode');
    Route::get('attendance/capture/kiosks/{kiosk}/qr-token', [HrAttendanceCaptureController::class, 'kioskQrToken'])
        ->whereNumber('kiosk')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.capture.kiosk-token');
    Route::post('attendance/capture/qr-scan', [HrAttendanceCaptureController::class, 'scanKioskQr'])
        ->middleware($requires(HrAuthorizationService::PUNCH_ATTENDANCE))
        ->name('attendance.capture.qr-scan');
    Route::post('attendance/capture/import/preview', [HrAttendanceCaptureController::class, 'previewImport'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.capture.import.preview');
    Route::post('attendance/capture/import/commit', [HrAttendanceCaptureController::class, 'commitImport'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.capture.import.commit');

    // Attendance Anomalies & Missing Punches
    Route::get('attendance/anomalies', [HrAttendanceAnomalyController::class, 'index'])
        ->middleware($requires(HrAuthorizationService::VIEW_ATTENDANCE))
        ->name('attendance.anomalies.index');
    Route::post('attendance/anomalies/scan', [HrAttendanceAnomalyController::class, 'scan'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.anomalies.scan');
    Route::post('attendance/anomalies/{record}/quick-resolve', [HrAttendanceAnomalyController::class, 'quickResolve'])
        ->whereNumber('record')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.anomalies.quick-resolve');

    // Attendance Geofences & Kiosks
    Route::get('attendance/geofences', [HrAttendanceGeofenceController::class, 'indexGeofences'])
        ->middleware($requires(HrAuthorizationService::VIEW_ATTENDANCE))
        ->name('attendance.geofences.index');
    Route::post('attendance/geofences', [HrAttendanceGeofenceController::class, 'storeGeofence'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.geofences.store');
    Route::put('attendance/geofences/{geofence}', [HrAttendanceGeofenceController::class, 'updateGeofence'])
        ->whereNumber('geofence')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.geofences.update');
    Route::delete('attendance/geofences/{geofence}', [HrAttendanceGeofenceController::class, 'destroyGeofence'])
        ->whereNumber('geofence')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.geofences.destroy');

    Route::get('attendance/kiosks', [HrAttendanceGeofenceController::class, 'indexKiosks'])
        ->middleware($requires(HrAuthorizationService::VIEW_ATTENDANCE))
        ->name('attendance.kiosks.index');
    Route::post('attendance/kiosks', [HrAttendanceGeofenceController::class, 'storeKiosk'])
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.kiosks.store');
    Route::put('attendance/kiosks/{kiosk}', [HrAttendanceGeofenceController::class, 'updateKiosk'])
        ->whereNumber('kiosk')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.kiosks.update');
    Route::delete('attendance/kiosks/{kiosk}', [HrAttendanceGeofenceController::class, 'destroyKiosk'])
        ->whereNumber('kiosk')
        ->middleware($requires(HrAuthorizationService::MANAGE_ATTENDANCE))
        ->name('attendance.kiosks.destroy');

    // Employee Attendance Credentials
    Route::get('employees/{employee}/credentials', [HrAttendanceCredentialController::class, 'index'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::VIEW_EMPLOYEES))
        ->name('employees.credentials.index');
    Route::post('employees/{employee}/credentials', [HrAttendanceCredentialController::class, 'store'])
        ->whereNumber('employee')
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.credentials.store');
    Route::patch('employees/{employee}/credentials/{credential}/status', [HrAttendanceCredentialController::class, 'updateStatus'])
        ->whereNumber(['employee', 'credential'])
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.credentials.status');
    Route::delete('employees/{employee}/credentials/{credential}', [HrAttendanceCredentialController::class, 'destroy'])
        ->whereNumber(['employee', 'credential'])
        ->middleware($requires(HrAuthorizationService::UPDATE_EMPLOYEES))
        ->name('employees.credentials.destroy');
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
    Route::get('leave-types/{leaveType}', [HrLeaveController::class, 'showLeaveType'])
        ->whereNumber('leaveType')
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-types.show');
    Route::match(['put', 'patch'], 'leave-types/{leaveType}', [HrLeaveController::class, 'updateLeaveType'])
        ->whereNumber('leaveType')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-types.update');
    Route::delete('leave-types/{leaveType}', [HrLeaveController::class, 'destroyLeaveType'])
        ->whereNumber('leaveType')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-types.destroy');
    Route::get('leave-policies', [HrLeaveController::class, 'leavePolicies'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-policies.index');
    Route::post('leave-policies', [HrLeaveController::class, 'storeLeavePolicy'])
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-policies.store');
    Route::get('leave-policies/{leavePolicy}', [HrLeaveController::class, 'showLeavePolicy'])
        ->whereNumber('leavePolicy')
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-policies.show');
    Route::match(['put', 'patch'], 'leave-policies/{leavePolicy}', [HrLeaveController::class, 'updateLeavePolicy'])
        ->whereNumber('leavePolicy')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-policies.update');
    Route::delete('leave-policies/{leavePolicy}', [HrLeaveController::class, 'destroyLeavePolicy'])
        ->whereNumber('leavePolicy')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('leave-policies.destroy');
    Route::get('leave-balances', [HrLeaveController::class, 'balances'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-balances.index');
    Route::get('leave-requests', [HrLeaveController::class, 'requests'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-requests.index');
    Route::get('leave-requests/{leaveRequest}', [HrLeaveController::class, 'showLeaveRequest'])
        ->whereNumber('leaveRequest')
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('leave-requests.show');
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
    Route::get('comp-off/claims', [HrLeaveController::class, 'compOffClaims'])
        ->middleware($requires(HrAuthorizationService::VIEW_LEAVES))
        ->name('comp-off.index');
    Route::post('comp-off/claim', [HrLeaveController::class, 'claimCompOff'])
        ->middleware($requires(HrAuthorizationService::APPLY_LEAVES))
        ->name('comp-off.claim');
    Route::patch('comp-off/claims/{claim}/action', [HrLeaveController::class, 'actionCompOffClaim'])
        ->whereNumber('claim')
        ->middleware($requires(HrAuthorizationService::MANAGE_LEAVES))
        ->name('comp-off.action');

    // Biometric Device Management & Logs
    Route::get('biometric/devices/lookup', [HrBiometricDeviceController::class, 'lookup'])
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.lookup');
    Route::get('biometric/devices', [HrBiometricDeviceController::class, 'index'])
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.index');
    Route::post('biometric/devices', [HrBiometricDeviceController::class, 'store'])
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.store');
    Route::get('biometric/devices/{device}', [HrBiometricDeviceController::class, 'show'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.show');
    Route::match(['put', 'patch'], 'biometric/devices/{device}', [HrBiometricDeviceController::class, 'update'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.update');
    Route::delete('biometric/devices/{device}', [HrBiometricDeviceController::class, 'destroy'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.destroy');
    Route::post('biometric/devices/{device}/ping', [HrBiometricDeviceController::class, 'ping'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.ping');
    Route::post('biometric/devices/{device}/sync', [HrBiometricDeviceController::class, 'sync'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::SYNC_BIOMETRIC_DEVICES))
        ->name('biometric.devices.sync');
    Route::get('biometric/devices/{device}/users', [HrBiometricDeviceController::class, 'users'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.users.index');
    Route::post('biometric/devices/{device}/users', [HrBiometricDeviceController::class, 'storeUser'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.users.store');
    Route::delete('biometric/devices/{device}/users/{mapping}', [HrBiometricDeviceController::class, 'destroyUser'])
        ->whereNumber(['device', 'mapping'])
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.users.destroy');
    Route::get('biometric/devices/{device}/raw-logs', [HrBiometricDeviceController::class, 'rawLogs'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::VIEW_BIOMETRIC_DEVICES))
        ->name('biometric.devices.raw-logs');
    Route::post('biometric/devices/{device}/reprocess', [HrBiometricDeviceController::class, 'reprocess'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::MANAGE_BIOMETRIC_DEVICES))
        ->name('biometric.devices.reprocess');
    Route::post('biometric/devices/{device}/upload-log', [HrBiometricPushReceiverController::class, 'uploadLog'])
        ->whereNumber('device')
        ->middleware($requires(HrAuthorizationService::SYNC_BIOMETRIC_DEVICES))
        ->name('biometric.devices.upload-log');
});

// Public Biometric Hardware Push Receivers (Outside Session Guard)
Route::prefix('api/v1/hr/biometric/push')->group(function (): void {
    Route::match(['get', 'post'], 'zkteco/cdata', [HrBiometricPushReceiverController::class, 'zkTecoCdata'])->name('api.v1.hr.biometric.push.zkteco.cdata');
    Route::get('zkteco/getrequest', [HrBiometricPushReceiverController::class, 'zkTecoGetRequest'])->name('api.v1.hr.biometric.push.zkteco.getrequest');
    Route::post('zkteco/devicecmd', [HrBiometricPushReceiverController::class, 'zkTecoDeviceCmd'])->name('api.v1.hr.biometric.push.zkteco.devicecmd');
    Route::post('hikvision', [HrBiometricPushReceiverController::class, 'hikvisionPush'])->name('api.v1.hr.biometric.push.hikvision');
    Route::post('webhook/{deviceCode}', [HrBiometricPushReceiverController::class, 'genericWebhook'])->name('api.v1.hr.biometric.push.webhook');
});

// ZKTeco direct root path aliases (for legacy firmware with hardcoded /iclock paths)
Route::match(['get', 'post'], 'iclock/cdata', [HrBiometricPushReceiverController::class, 'zkTecoCdata'])->name('iclock.cdata');
Route::get('iclock/getrequest', [HrBiometricPushReceiverController::class, 'zkTecoGetRequest'])->name('iclock.getrequest');
Route::post('iclock/devicecmd', [HrBiometricPushReceiverController::class, 'zkTecoDeviceCmd'])->name('iclock.devicecmd');
