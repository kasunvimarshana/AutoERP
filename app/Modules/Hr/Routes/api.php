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
use Modules\Hr\Services\HrAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
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
});
