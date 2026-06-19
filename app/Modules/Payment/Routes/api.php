<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\ChequePrintController;
use Modules\Payment\Http\Controllers\ChequeTemplateController;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\PaymentMethodController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/payments')->middleware($middleware)->name('api.v1.payments.')->group(function (): void {
    Route::get('cheque-templates', [ChequeTemplateController::class, 'index'])->name('cheque-templates.index');
    Route::post('cheque-templates', [ChequeTemplateController::class, 'store'])->name('cheque-templates.store');
    Route::get('cheque-templates/{id}', [ChequeTemplateController::class, 'show'])->whereNumber('id')->name('cheque-templates.show');
    Route::put('cheque-templates/{id}', [ChequeTemplateController::class, 'update'])->whereNumber('id')->name('cheque-templates.update');
    Route::delete('cheque-templates/{id}', [ChequeTemplateController::class, 'destroy'])->whereNumber('id')->name('cheque-templates.destroy');
    Route::get('methods', [PaymentMethodController::class, 'index'])->name('methods.index');
    Route::post('methods', [PaymentMethodController::class, 'store'])->name('methods.store');
    Route::get('methods/{id}', [PaymentMethodController::class, 'show'])->whereNumber('id')->name('methods.show');
    Route::put('methods/{id}', [PaymentMethodController::class, 'update'])->whereNumber('id')->name('methods.update');
    Route::post('methods/{id}/activate', [PaymentMethodController::class, 'activate'])->whereNumber('id')->name('methods.activate');
    Route::post('methods/{id}/deactivate', [PaymentMethodController::class, 'deactivate'])->whereNumber('id')->name('methods.deactivate');
    Route::delete('methods/{id}', [PaymentMethodController::class, 'destroy'])->whereNumber('id')->name('methods.destroy');
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('{payment}', [PaymentController::class, 'show'])->whereNumber('payment')->name('show');
    Route::post('{payment}/submit-approval', [PaymentController::class, 'submitForApproval'])->whereNumber('payment')->name('submit-approval');
    Route::post('{payment}/approve', [PaymentController::class, 'approve'])->whereNumber('payment')->name('approve');
    Route::post('{payment}/post', [PaymentController::class, 'post'])->whereNumber('payment')->name('post');
    Route::post('{payment}/void', [PaymentController::class, 'void'])->whereNumber('payment')->name('void');
    Route::post('{payment}/reverse', [PaymentController::class, 'reverse'])->whereNumber('payment')->name('reverse');
    Route::post('{payment}/allocations', [PaymentController::class, 'allocate'])->whereNumber('payment')->name('allocations.store');
    Route::get('{payment}/allocations', [PaymentController::class, 'allocations'])->whereNumber('payment')->name('allocations.index');
    Route::get('{payment}/unapplied-balance', [PaymentController::class, 'unappliedBalance'])->whereNumber('payment')->name('unapplied-balance');
    Route::post('{payment}/lines/{line}/settlement', [PaymentController::class, 'settleLine'])
        ->whereNumber('payment')
        ->whereNumber('line')
        ->name('lines.settlement');
    Route::post('{payment}/refunds', [PaymentController::class, 'refund'])->whereNumber('payment')->name('refunds.store');
    Route::get('{payment}/lines/{line}/cheque-print/preview', [ChequePrintController::class, 'preview'])
        ->whereNumber('payment')
        ->whereNumber('line')
        ->name('lines.cheque-print.preview');
    Route::post('{payment}/lines/{line}/cheque-print', [ChequePrintController::class, 'markPrinted'])
        ->whereNumber('payment')
        ->whereNumber('line')
        ->name('lines.cheque-print.print');
});
