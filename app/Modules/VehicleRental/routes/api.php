<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLessorAgreementController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLesseeAgreementController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLessorRunningChartController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLesseeRunningChartController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLessorAgreementCreditNoteController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLessorAgreementDebitNoteController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLesseeAgreementCreditNoteController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalLesseeAgreementDebitNoteController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/vehicle-rental')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('vehiclerental.')
    ->group(function (): void {
        Route::apiResource('vehicle-rental-lessor-agreements', VehicleRentalLessorAgreementController::class);
        Route::apiResource('vehicle-rental-lessee-agreements', VehicleRentalLesseeAgreementController::class);
        Route::apiResource('vehicle-rental-lessor-running-charts', VehicleRentalLessorRunningChartController::class);
        Route::apiResource('vehicle-rental-lessee-running-charts', VehicleRentalLesseeRunningChartController::class);
        Route::apiResource('vehicle-rental-lessor-agreement-credit-notes', VehicleRentalLessorAgreementCreditNoteController::class);
        Route::apiResource('vehicle-rental-lessor-agreement-debit-notes', VehicleRentalLessorAgreementDebitNoteController::class);
        Route::apiResource('vehicle-rental-lessee-agreement-credit-notes', VehicleRentalLesseeAgreementCreditNoteController::class);
        Route::apiResource('vehicle-rental-lessee-agreement-debit-notes', VehicleRentalLesseeAgreementDebitNoteController::class);
    });