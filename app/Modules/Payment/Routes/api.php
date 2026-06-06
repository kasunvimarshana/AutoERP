<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/payments')->middleware($middleware)->name('api.v1.payments.')->group(function (): void {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('{payment}', [PaymentController::class, 'show'])->whereNumber('payment')->name('show');
    Route::post('{payment}/approve', [PaymentController::class, 'approve'])->whereNumber('payment')->name('approve');
    Route::post('{payment}/post', [PaymentController::class, 'post'])->whereNumber('payment')->name('post');
    Route::post('{payment}/void', [PaymentController::class, 'void'])->whereNumber('payment')->name('void');
    Route::post('{payment}/reverse', [PaymentController::class, 'reverse'])->whereNumber('payment')->name('reverse');
    Route::post('{payment}/allocations', [PaymentController::class, 'allocate'])->whereNumber('payment')->name('allocations.store');
    Route::get('{payment}/allocations', [PaymentController::class, 'allocations'])->whereNumber('payment')->name('allocations.index');
    Route::get('{payment}/unapplied-balance', [PaymentController::class, 'unappliedBalance'])->whereNumber('payment')->name('unapplied-balance');
    Route::post('{payment}/refunds', [PaymentController::class, 'refund'])->whereNumber('payment')->name('refunds.store');
});
