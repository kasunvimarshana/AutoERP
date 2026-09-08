<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Tenancy\TenantFeature;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Controllers\AgreementController;

Route::prefix('api/v1/vehicle-rental/{kind}/agreements')->whereIn('kind', array_column(AgreementKind::cases(), 'value'))->middleware([
    'api', 'auth:'.config('module-auth.protected_route_guard', 'auth-api'),
    config('core.current_user.middleware_alias', 'current.user'), config('core.current_tenant.middleware_alias', 'current.tenant'),
    config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required', 'tenant.feature:'.TenantFeature::VEHICLE_RENTAL,
])->group(function (): void {
    // Services authorize the customer and owner sides separately on every read and write.
    Route::get('/', [AgreementController::class, 'index']);
    Route::post('/', [AgreementController::class, 'store']);
    Route::get('{agreement}', [AgreementController::class, 'show'])->whereNumber('agreement');
    Route::put('{agreement}', [AgreementController::class, 'update'])->whereNumber('agreement');
    Route::get('{agreement}/history', [AgreementController::class, 'history'])->whereNumber('agreement');
    Route::post('{agreement}/{action}', [AgreementController::class, 'transition'])->whereNumber('agreement')->whereIn('action', [AgreementAction::Activate->value, AgreementAction::Close->value]);
});
