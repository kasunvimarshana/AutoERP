<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\EmployeeController;
use Modules\Hr\Http\Controllers\EmployeeRelationController;
use Modules\Hr\Http\Controllers\HrCertificationController;
use Modules\Hr\Http\Controllers\HrDepartmentController;
use Modules\Hr\Http\Controllers\HrDesignationController;
use Modules\Hr\Http\Controllers\HrEmploymentTypeController;
use Modules\Hr\Http\Controllers\HrLicenseController;
use Modules\Hr\Http\Controllers\HrSkillController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    'tenant.feature:hr',
    'tenant.permission:hr.view',
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
];

Route::prefix('api/v1/hr')->middleware($middleware)->name('api.v1.hr.')->group(function (): void {
    Route::get('employees/lookup/{kind?}', [EmployeeController::class, 'lookup'])
        ->whereIn('kind', ['active', 'available', 'service-available', 'by-skill', 'by-designation'])
        ->name('employees.lookup');
    Route::post('employees/with-relations', [EmployeeController::class, 'storeWithRelations'])
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.with-relations.store');
    Route::patch('employees/{employee}/activate', [EmployeeController::class, 'activate'])
        ->whereNumber('employee')
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.activate');
    Route::patch('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])
        ->whereNumber('employee')
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.deactivate');
    Route::patch('employees/{employee}/status', [EmployeeController::class, 'changeStatus'])
        ->whereNumber('employee')
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.status');
    Route::patch('employees/{employee}/availability', [EmployeeController::class, 'changeAvailability'])
        ->whereNumber('employee')
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.availability.update');

    $relations = [
        'contacts' => ['storeContact', 'updateContact', 'destroyContact', 'contact'],
        'addresses' => ['storeAddress', 'updateAddress', 'destroyAddress', 'address'],
        'documents' => ['storeDocument', 'updateDocument', 'destroyDocument', 'document'],
        'skills' => ['storeSkill', 'updateSkill', 'destroySkill', 'assignment'],
        'certifications' => ['storeCertification', 'updateCertification', 'destroyCertification', 'assignment'],
        'licenses' => ['storeLicense', 'updateLicense', 'destroyLicense', 'assignment'],
        'rates' => ['storeRate', 'updateRate', 'destroyRate', 'rate'],
    ];

    foreach ($relations as $relation => [$store, $update, $destroy, $child]) {
        $routeName = "employees.{$relation}";

        Route::get("employees/{employee}/{$relation}", [EmployeeRelationController::class, $relation])
            ->whereNumber('employee')
            ->name("{$routeName}.index");
        Route::post("employees/{employee}/{$relation}", [EmployeeRelationController::class, $store])
            ->whereNumber('employee')
            ->middleware('tenant.permission:hr.manage')
            ->name("{$routeName}.store");
        Route::put("employees/{employee}/{$relation}/{{$child}}", [EmployeeRelationController::class, $update])
            ->whereNumber(['employee', $child])
            ->middleware('tenant.permission:hr.manage')
            ->name("{$routeName}.update");
        Route::delete("employees/{employee}/{$relation}/{{$child}}", [EmployeeRelationController::class, $destroy])
            ->whereNumber(['employee', $child])
            ->middleware('tenant.permission:hr.manage')
            ->name("{$routeName}.destroy");
    }

    Route::get('employees/{employee}/availability', [EmployeeRelationController::class, 'availability'])
        ->whereNumber('employee')
        ->name('employees.availability.show');
    Route::post('employees/{employee}/availability', [EmployeeRelationController::class, 'storeAvailability'])
        ->whereNumber('employee')
        ->middleware('tenant.permission:hr.manage')
            ->name('employees.availability.store');
    Route::get('employees/{employee}/status-history', [EmployeeRelationController::class, 'statusHistory'])
        ->whereNumber('employee')
        ->name('employees.status-history.index');

    Route::apiResource('employees', EmployeeController::class)->only(['index', 'show']);
    Route::apiResource('employees', EmployeeController::class)->except(['index', 'show'])
        ->middleware('tenant.permission:hr.manage');

    foreach ([
        'departments' => HrDepartmentController::class,
        'designations' => HrDesignationController::class,
        'employment-types' => HrEmploymentTypeController::class,
        'skills' => HrSkillController::class,
        'certifications' => HrCertificationController::class,
        'licenses' => HrLicenseController::class,
    ] as $path => $controller) {
        Route::get("{$path}/lookup", [$controller, 'lookup'])->name("{$path}.lookup");
        Route::apiResource($path, $controller)->only(['index', 'show']);
        Route::apiResource($path, $controller)->except(['index', 'show'])
            ->middleware('tenant.permission:hr.manage');
    }
});
