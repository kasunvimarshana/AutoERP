<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Voucher\Presentation\Http\Controllers\VoucherController;
use Modules\Voucher\Presentation\Http\Controllers\VoucherTypeController;
use Modules\Voucher\Presentation\Http\Controllers\VoucherUtilityController;
use Modules\Voucher\Presentation\Http\Controllers\VoucherWorkflowController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/voucher')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('voucher.')
    ->group(function (): void {
        Route::apiResource('types', VoucherTypeController::class)->except(['destroy']);
        Route::patch('types/{type}/activate', [VoucherTypeController::class, 'activate'])
            ->name('types.activate');
        Route::patch('types/{type}/deactivate', [VoucherTypeController::class, 'deactivate'])
            ->name('types.deactivate');

        Route::apiResource('vouchers', VoucherController::class);
        Route::put('vouchers/{voucher}/lines', [VoucherController::class, 'upsertLines'])
            ->name('vouchers.lines.upsert');

        Route::get('vouchers/{voucher}/allocations', [VoucherController::class, 'allocations'])
            ->name('vouchers.allocations.index');
        Route::post('vouchers/{voucher}/allocations', [VoucherController::class, 'addAllocation'])
            ->name('vouchers.allocations.store');
        Route::patch('allocations/{allocation}', [VoucherController::class, 'updateAllocation'])
            ->name('allocations.update');

        Route::post('vouchers/{voucher}/submit', [VoucherWorkflowController::class, 'submit'])
            ->name('vouchers.submit');
        Route::post('vouchers/{voucher}/approve', [VoucherWorkflowController::class, 'approve'])
            ->name('vouchers.approve');
        Route::post('vouchers/{voucher}/reject', [VoucherWorkflowController::class, 'reject'])
            ->name('vouchers.reject');
        Route::post('vouchers/{voucher}/post', [VoucherWorkflowController::class, 'post'])
            ->name('vouchers.post');
        Route::post('vouchers/{voucher}/cancel', [VoucherWorkflowController::class, 'cancel'])
            ->name('vouchers.cancel');
        Route::post('vouchers/{voucher}/reverse', [VoucherWorkflowController::class, 'reverse'])
            ->name('vouchers.reverse');
        Route::get('vouchers/{voucher}/history', [VoucherWorkflowController::class, 'history'])
            ->name('vouchers.history');

        Route::post('utilities/preview-number', [VoucherUtilityController::class, 'previewNumber'])
            ->name('utilities.preview-number');
        Route::post('utilities/validate-balance', [VoucherUtilityController::class, 'validateBalance'])
            ->name('utilities.validate-balance');
        Route::post('utilities/validate-payment-method', [VoucherUtilityController::class, 'validatePaymentMethod'])
            ->name('utilities.validate-payment-method');
        Route::get('utilities/{voucher}/preview-posting', [VoucherUtilityController::class, 'previewPosting'])
            ->name('utilities.preview-posting');
    });
