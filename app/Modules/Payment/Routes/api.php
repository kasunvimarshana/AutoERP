<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Http\Controllers\ChequePrintController;
use Modules\Payment\Http\Controllers\ChequeTemplateController;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\PaymentMethodController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:payment',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/payments')->middleware($middleware)->name('api.v1.payments.')->group(function () use ($requires): void {
    Route::middleware($requires(PaymentPermission::TEMPLATES_VIEW))->group(function (): void {
        Route::get('cheque-templates', [ChequeTemplateController::class, 'index'])->name('cheque-templates.index');
        Route::get('cheque-templates/{id}', [ChequeTemplateController::class, 'show'])->whereNumber('id')->name('cheque-templates.show');
    });
    Route::post('cheque-templates', [ChequeTemplateController::class, 'store'])->middleware($requires(PaymentPermission::TEMPLATES_CREATE))->name('cheque-templates.store');
    Route::put('cheque-templates/{id}', [ChequeTemplateController::class, 'update'])->whereNumber('id')->middleware($requires(PaymentPermission::TEMPLATES_UPDATE))->name('cheque-templates.update');
    Route::delete('cheque-templates/{id}', [ChequeTemplateController::class, 'destroy'])->whereNumber('id')->middleware($requires(PaymentPermission::TEMPLATES_DELETE))->name('cheque-templates.destroy');

    Route::middleware($requires(PaymentPermission::METHODS_VIEW))->group(function (): void {
        Route::get('methods', [PaymentMethodController::class, 'index'])->name('methods.index');
        Route::get('methods/{id}', [PaymentMethodController::class, 'show'])->whereNumber('id')->name('methods.show');
    });
    Route::post('methods', [PaymentMethodController::class, 'store'])->middleware($requires(PaymentPermission::METHODS_CREATE))->name('methods.store');
    Route::put('methods/{id}', [PaymentMethodController::class, 'update'])->whereNumber('id')->middleware($requires(PaymentPermission::METHODS_UPDATE))->name('methods.update');
    Route::post('methods/{id}/activate', [PaymentMethodController::class, 'activate'])->whereNumber('id')->middleware($requires(PaymentPermission::METHODS_UPDATE))->name('methods.activate');
    Route::post('methods/{id}/deactivate', [PaymentMethodController::class, 'deactivate'])->whereNumber('id')->middleware($requires(PaymentPermission::METHODS_UPDATE))->name('methods.deactivate');
    Route::delete('methods/{id}', [PaymentMethodController::class, 'destroy'])->whereNumber('id')->middleware($requires(PaymentPermission::METHODS_DELETE))->name('methods.destroy');

    Route::get('/', [PaymentController::class, 'index'])->middleware($requires(PaymentPermission::PAYMENTS_VIEW))->name('index');
    Route::post('/', [PaymentController::class, 'store'])->middleware($requires(PaymentPermission::PAYMENTS_CREATE))->name('store');
    Route::get('{payment}', [PaymentController::class, 'show'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_VIEW))->name('show');
    Route::post('{payment}/submit-approval', [PaymentController::class, 'submitForApproval'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_SUBMIT))->name('submit-approval');
    Route::post('{payment}/approve', [PaymentController::class, 'approve'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_APPROVE))->name('approve');
    Route::post('{payment}/post', [PaymentController::class, 'post'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_POST))->name('post');
    Route::post('{payment}/void', [PaymentController::class, 'void'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_VOID))->name('void');
    Route::post('{payment}/reverse', [PaymentController::class, 'reverse'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_REVERSE))->name('reverse');
    Route::post('{payment}/allocations', [PaymentController::class, 'allocate'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_ALLOCATE))->name('allocations.store');
    Route::get('{payment}/allocations', [PaymentController::class, 'allocations'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_VIEW))->name('allocations.index');
    Route::get('{payment}/unapplied-balance', [PaymentController::class, 'unappliedBalance'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_VIEW))->name('unapplied-balance');
    Route::post('{payment}/lines/{line}/settlement', [PaymentController::class, 'settleLine'])
        ->whereNumber(['payment', 'line'])
        ->middleware($requires(PaymentPermission::PAYMENTS_SETTLE))
        ->name('lines.settlement');
    Route::post('{payment}/refunds', [PaymentController::class, 'refund'])->whereNumber('payment')->middleware($requires(PaymentPermission::PAYMENTS_REFUND))->name('refunds.store');
    Route::get('{payment}/lines/{line}/cheque-print/preview', [ChequePrintController::class, 'preview'])
        ->whereNumber(['payment', 'line'])
        ->middleware($requires(PaymentPermission::CHEQUES_PREVIEW))
        ->name('lines.cheque-print.preview');
    Route::post('{payment}/lines/{line}/cheque-print', [ChequePrintController::class, 'markPrinted'])
        ->whereNumber(['payment', 'line'])
        ->middleware($requires(PaymentPermission::CHEQUES_PRINT))
        ->name('lines.cheque-print.print');
});
