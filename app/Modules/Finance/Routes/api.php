<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:finance',
];

Route::prefix('api/v1/finance')->middleware($middleware)->name('api.v1.finance.')->group(function (): void {
    Route::get('accounts', [FinanceController::class, 'accounts'])->name('accounts.index');
    Route::post('accounts', [FinanceController::class, 'createAccount'])->name('accounts.store');
    Route::patch('accounts/{account}', [FinanceController::class, 'updateAccount'])->whereNumber('account')->name('accounts.update');
    Route::get('accounts/{account}', [FinanceController::class, 'showAccount'])->whereNumber('account')->name('accounts.show');
    Route::get('accounts/{account}/balance', [FinanceController::class, 'accountBalance'])->whereNumber('account')->name('accounts.balance');
    Route::get('lookups', [FinanceController::class, 'lookups'])->name('lookups');
    Route::get('posting-profiles', [FinanceController::class, 'postingProfiles'])->name('posting-profiles.index');
    Route::post('posting-profiles', [FinanceController::class, 'createPostingProfile'])->name('posting-profiles.store');
    Route::patch('posting-profiles/{profile}', [FinanceController::class, 'updatePostingProfile'])->whereNumber('profile')->name('posting-profiles.update');
    Route::get('journals', [FinanceController::class, 'journals'])->name('journals.index');
    Route::post('journals', [FinanceController::class, 'createJournal'])->name('journals.store');
    Route::get('journals/{journal}', [FinanceController::class, 'showJournal'])->whereNumber('journal')->name('journals.show');
    Route::patch('journals/{journal}', [FinanceController::class, 'updateJournal'])->whereNumber('journal')->name('journals.update');
    Route::post('journals/{journal}/cancel', [FinanceController::class, 'cancelJournal'])->whereNumber('journal')->name('journals.cancel');
    Route::post('journals/{journal}/post', [FinanceController::class, 'postJournal'])->whereNumber('journal')->name('journals.post');
    Route::post('journals/{journal}/reverse', [FinanceController::class, 'reverseJournal'])->whereNumber('journal')->name('journals.reverse');
    Route::get('ledger-entries', [FinanceController::class, 'ledger'])->name('ledger-entries.index');
    Route::get('account-balances', [FinanceController::class, 'accountBalances'])->name('account-balances');
    Route::get('trial-balance', [FinanceController::class, 'trialBalance'])->name('trial-balance');
    Route::get('profit-and-loss', [FinanceController::class, 'profitAndLoss'])->name('profit-and-loss');
    Route::get('balance-sheet', [FinanceController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('cash-flow', [FinanceController::class, 'cashFlow'])->name('cash-flow');
    Route::get('ar-aging', [FinanceController::class, 'arAging'])->name('ar-aging');
    Route::get('ap-aging', [FinanceController::class, 'apAging'])->name('ap-aging');
    Route::get('tax-liability', [FinanceController::class, 'taxLiability'])->name('tax-liability');
    Route::get('tax-reconciliation', [FinanceController::class, 'taxReconciliation'])->name('tax-reconciliation');
    Route::post('currency-revaluations', [FinanceController::class, 'postCurrencyRevaluation'])->name('currency-revaluations.store');
    Route::get('fiscal-years', [FinanceController::class, 'fiscalYears'])->name('fiscal-years.index');
    Route::patch('fiscal-years/{year}/status', [FinanceController::class, 'updateFiscalYearStatus'])->whereNumber('year')->name('fiscal-years.status');
    Route::get('fiscal-periods', [FinanceController::class, 'fiscalPeriods'])->name('fiscal-periods.index');
    Route::patch('fiscal-periods/{period}/status', [FinanceController::class, 'updateFiscalPeriodStatus'])->whereNumber('period')->name('fiscal-periods.status');
    Route::get('bank-reconciliations', [FinanceController::class, 'bankReconciliations'])->name('bank-reconciliations.index');
    Route::post('bank-reconciliations', [FinanceController::class, 'createBankReconciliation'])->name('bank-reconciliations.store');
    Route::get('bank-reconciliations/{reconciliation}', [FinanceController::class, 'showBankReconciliation'])->whereNumber('reconciliation')->name('bank-reconciliations.show');
    Route::post('bank-reconciliations/{reconciliation}/complete', [FinanceController::class, 'completeBankReconciliation'])->whereNumber('reconciliation')->name('bank-reconciliations.complete');
    Route::post('bank-reconciliations/{reconciliation}/lines/{line}/match', [FinanceController::class, 'matchBankStatementLine'])->whereNumber(['reconciliation', 'line'])->name('bank-reconciliations.lines.match');
    Route::post('bank-reconciliations/{reconciliation}/lines/{line}/unmatch', [FinanceController::class, 'unmatchBankStatementLine'])->whereNumber(['reconciliation', 'line'])->name('bank-reconciliations.lines.unmatch');
    Route::get('budgets', [FinanceController::class, 'budgets'])->name('budgets.index');
    Route::post('budgets', [FinanceController::class, 'createBudget'])->name('budgets.store');
    Route::get('budgets/{budget}', [FinanceController::class, 'showBudget'])->whereNumber('budget')->name('budgets.show');
    Route::patch('budgets/{budget}', [FinanceController::class, 'updateBudget'])->whereNumber('budget')->name('budgets.update');
    Route::get('budgets/{budget}/actuals', [FinanceController::class, 'budgetActuals'])->whereNumber('budget')->name('budgets.actuals');
});
