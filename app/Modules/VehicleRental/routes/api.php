<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalAgreementController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalIntegrationController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalManagementController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalRunningChartController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalWorkflowController;

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
        Route::apiResource('agreements', VehicleRentalAgreementController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['agreements' => 'agreement']);
        Route::apiResource('running-charts', VehicleRentalRunningChartController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['running-charts' => 'running_chart']);

        Route::post(
            'agreements/{agreementId}/lines/sync',
            [VehicleRentalManagementController::class, 'syncAgreementLines'],
        )->name('agreements.lines.sync');
        Route::post(
            'agreements/{agreementId}/rates/sync',
            [VehicleRentalManagementController::class, 'syncAgreementRates'],
        )->name('agreements.rates.sync');
        Route::post(
            'agreements/{agreementId}/rate-rules/sync',
            [VehicleRentalManagementController::class, 'syncRateRules'],
        )->name('agreements.rate-rules.sync');
        Route::post(
            'agreements/{agreementId}/extra-charges/sync',
            [VehicleRentalManagementController::class, 'syncExtraCharges'],
        )->name('agreements.extra-charges.sync');
        Route::post(
            'agreements/{agreementId}/billing-preview',
            [VehicleRentalManagementController::class, 'billingPreview'],
        )->name('agreements.billing-preview');
        Route::post(
            'running-charts/{runningChartId}/lines/sync',
            [VehicleRentalManagementController::class, 'syncRunningChartLines'],
        )->name('running-charts.lines.sync');

        Route::post('replacements', [VehicleRentalManagementController::class, 'storeReplacement'])
            ->name('replacements.store');
        Route::put('replacements/{replacementId}', [VehicleRentalManagementController::class, 'updateReplacement'])
            ->name('replacements.update');
        Route::post('breakdowns', [VehicleRentalManagementController::class, 'storeBreakdown'])
            ->name('breakdowns.store');
        Route::put('breakdowns/{breakdownId}', [VehicleRentalManagementController::class, 'updateBreakdown'])
            ->name('breakdowns.update');

        Route::get('settings', [VehicleRentalManagementController::class, 'showSettings'])
            ->name('settings.show');
        Route::post('settings', [VehicleRentalManagementController::class, 'upsertSettings'])
            ->name('settings.upsert');
        Route::post('settings/initialize', [VehicleRentalManagementController::class, 'initializeSettings'])
            ->name('settings.initialize');
        Route::get(
            'status-history/{entityType}/{entityId}',
            [VehicleRentalManagementController::class, 'statusHistory'],
        )->name('status-history.show');
        Route::get('vehicle-availability', [VehicleRentalManagementController::class, 'vehicleAvailability'])
            ->name('vehicle-availability.show');
        Route::get('provider-payables', [VehicleRentalManagementController::class, 'providerPayables'])
            ->name('provider-payables.index');
        Route::get('rental-vehicles', [VehicleRentalManagementController::class, 'rentalVehicles'])
            ->name('rental-vehicles.index');

        Route::post(
            'workflow/agreements/{agreementId}/transition',
            [VehicleRentalWorkflowController::class, 'transitionAgreement'],
        )->name('workflow.agreements.transition');
        Route::post(
            'workflow/running-charts/{runningChartId}/transition',
            [VehicleRentalWorkflowController::class, 'transitionRunningChart'],
        )->name('workflow.running-charts.transition');
        Route::post(
            'workflow/agreements/{agreementId}/invoice',
            [VehicleRentalWorkflowController::class, 'createInvoice'],
        )->name('workflow.invoice');
        Route::post(
            'workflow/agreements/{agreementId}/payments/allocate',
            [VehicleRentalWorkflowController::class, 'allocateCustomerPayment'],
        )->name('workflow.payments.allocate');
        Route::post(
            'workflow/agreements/{agreementId}/provider-payables',
            [VehicleRentalWorkflowController::class, 'createProviderPayable'],
        )->name('workflow.provider-payables.store');
        Route::post(
            'workflow/provider-payables/{providerPayableId}/payments/allocate',
            [VehicleRentalWorkflowController::class, 'allocateProviderPayment'],
        )->name('workflow.provider-payables.payments.allocate');
        Route::post(
            'workflow/{entityType}/{entityId}/finance/post',
            [VehicleRentalWorkflowController::class, 'postFinance'],
        )->name('workflow.finance.post');
        Route::post(
            'workflow/{entityType}/{entityId}/finance/reverse',
            [VehicleRentalWorkflowController::class, 'reverseFinance'],
        )->name('workflow.finance.reverse');

        Route::post(
            'integration/agreements/{agreementId}/invoice',
            [VehicleRentalIntegrationController::class, 'createRentalInvoice'],
        )->name('integration.invoice');
        Route::post(
            'integration/agreements/{agreementId}/payments/allocate',
            [VehicleRentalIntegrationController::class, 'allocateRentalPayment'],
        )->name('integration.payments.allocate');
        Route::post(
            'integration/agreements/{agreementId}/provider-payables',
            [VehicleRentalIntegrationController::class, 'createRentalProviderPayable'],
        )->name('integration.provider-payables.store');
        Route::post(
            'integration/provider-payables/{providerPayableId}/payments/allocate',
            [VehicleRentalIntegrationController::class, 'allocateProviderPayablePayment'],
        )->name('integration.provider-payables.payments.allocate');
    });
