<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/finance')->middleware($middleware)->name('api.v1.finance.')->group(function (): void {
    Route::get('accounts', [FinanceController::class, 'accounts'])->name('accounts.index');
    Route::post('accounts', [FinanceController::class, 'createAccount'])->name('accounts.store');
    Route::get('accounts/{account}', [FinanceController::class, 'showAccount'])->whereNumber('account')->name('accounts.show');
    Route::get('accounts/{account}/balance', [FinanceController::class, 'accountBalance'])->whereNumber('account')->name('accounts.balance');
    Route::post('journals', [FinanceController::class, 'createJournal'])->name('journals.store');
    Route::post('journals/{journal}/post', [FinanceController::class, 'postJournal'])->whereNumber('journal')->name('journals.post');
    Route::post('journals/{journal}/reverse', [FinanceController::class, 'reverseJournal'])->whereNumber('journal')->name('journals.reverse');
    Route::get('ledger-entries', [FinanceController::class, 'ledger'])->name('ledger-entries.index');
    Route::get('trial-balance', [FinanceController::class, 'trialBalance'])->name('trial-balance');
});
