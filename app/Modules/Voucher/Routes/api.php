<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Voucher\Http\Controllers\VoucherController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
];

Route::prefix('api/v1/vouchers')->middleware($middleware)->name('api.v1.vouchers.')->group(function (): void {
    Route::get('types', [VoucherController::class, 'types'])->name('types');
    Route::get('/', [VoucherController::class, 'index'])->name('index');
    Route::get('{voucherType}/{source}', [VoucherController::class, 'show'])
        ->whereNumber('source')
        ->name('show');
    Route::get('{voucherType}/{source}/print', [VoucherController::class, 'print'])
        ->whereNumber('source')
        ->name('print');
});
