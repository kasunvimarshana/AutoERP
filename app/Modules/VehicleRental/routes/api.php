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
        Route::apiResource(
            'vehicle-rental-lessor-agreements',
            VehicleRentalLessorAgreementController::class,
        )
            ->parameters(['vehicle-rental-lessor-agreements' => 'lessor_agreement']);
        Route::apiResource(
            'vehicle-rental-lessee-agreements',
            VehicleRentalLesseeAgreementController::class,
        )
            ->parameters(['vehicle-rental-lessee-agreements' => 'lessee_agreement']);
        Route::apiResource(
            'vehicle-rental-lessor-running-charts',
            VehicleRentalLessorRunningChartController::class,
        )
            ->parameters(['vehicle-rental-lessor-running-charts' => 'lessor_chart']);
        Route::apiResource(
            'vehicle-rental-lessee-running-charts',
            VehicleRentalLesseeRunningChartController::class,
        )
            ->parameters(['vehicle-rental-lessee-running-charts' => 'lessee_chart']);
        Route::apiResource(
            'vehicle-rental-lessor-agreement-credit-notes',
            VehicleRentalLessorAgreementCreditNoteController::class,
        )
            ->parameters(['vehicle-rental-lessor-agreement-credit-notes' => 'lessor_credit_note']);
        Route::apiResource(
            'vehicle-rental-lessor-agreement-debit-notes',
            VehicleRentalLessorAgreementDebitNoteController::class,
        )
            ->parameters(['vehicle-rental-lessor-agreement-debit-notes' => 'lessor_debit_note']);
        Route::apiResource(
            'vehicle-rental-lessee-agreement-credit-notes',
            VehicleRentalLesseeAgreementCreditNoteController::class,
        )
            ->parameters(['vehicle-rental-lessee-agreement-credit-notes' => 'lessee_credit_note']);
        Route::apiResource(
            'vehicle-rental-lessee-agreement-debit-notes',
            VehicleRentalLesseeAgreementDebitNoteController::class,
        )
            ->parameters(['vehicle-rental-lessee-agreement-debit-notes' => 'lessee_debit_note']);
    });
