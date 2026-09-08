<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Tenancy\TenantFeature;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Http\Controllers\AgreementController;
use Modules\VehicleRental\Http\Controllers\RunningChartController;
use Modules\VehicleRental\Http\Controllers\VehicleUseController;

Route::prefix('api/v1/vehicle-rental')->middleware([
    'api', 'auth:'.config('module-auth.protected_route_guard', 'auth-api'),
    config('core.current_user.middleware_alias', 'current.user'), config('core.current_tenant.middleware_alias', 'current.tenant'),
    config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required', 'tenant.feature:'.TenantFeature::VEHICLE_RENTAL,
])->group(function (): void {
    Route::prefix('{kind}/agreements')->whereIn('kind', array_column(AgreementKind::cases(), 'value'))->group(function (): void {
        // Services authorize the customer and owner sides separately on every read and write.
        Route::get('/', [AgreementController::class, 'index']);
        Route::post('/', [AgreementController::class, 'store']);
        Route::get('{agreement}', [AgreementController::class, 'show'])->whereNumber('agreement');
        Route::put('{agreement}', [AgreementController::class, 'update'])->whereNumber('agreement');
        Route::get('{agreement}/history', [AgreementController::class, 'history'])->whereNumber('agreement');
        Route::post('{agreement}/{action}', [AgreementController::class, 'transition'])->whereNumber('agreement')->whereIn('action', [AgreementAction::Activate->value, AgreementAction::Close->value]);
    });
    Route::get('customer/agreements/{agreement}/vehicles', [VehicleUseController::class, 'index'])->whereNumber('agreement');
    Route::post('customer/agreements/{agreement}/vehicles', [VehicleUseController::class, 'store'])->whereNumber('agreement');
    Route::get('vehicles/{vehicle}/sources', [VehicleUseController::class, 'sources'])->whereNumber('vehicle');
    Route::post('vehicle-uses/{use}/replace', [VehicleUseController::class, 'replace'])->whereNumber('use');
    Route::get('vehicle-uses/{use}/history', [VehicleUseController::class, 'history'])->whereNumber('use');
    Route::post('vehicle-uses/{use}/{action}', [VehicleUseController::class, 'transition'])->whereNumber('use')->whereIn('action', [VehicleUseAction::Handover->value, VehicleUseAction::ReturnVehicle->value, VehicleUseAction::Cancel->value]);
    Route::get('vehicle-uses/{use}/running-charts', [RunningChartController::class, 'index'])->whereNumber('use');
    Route::post('vehicle-uses/{use}/running-charts', [RunningChartController::class, 'store'])->whereNumber('use');
    Route::put('running-charts/{chart}', [RunningChartController::class, 'update'])->whereNumber('chart');
    Route::get('running-charts/{chart}/history', [RunningChartController::class, 'history'])->whereNumber('chart');
    Route::post('running-charts/{chart}/{action}', [RunningChartController::class, 'transition'])->whereNumber('chart')->whereIn('action', [RunningChartAction::Finalize->value, RunningChartAction::Reverse->value]);
});
