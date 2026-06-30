<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Constants\FinancePermission;
use Modules\Finance\Http\Controllers\FinanceConfigurationController;
use Modules\Finance\Http\Controllers\FinanceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:finance',
];

$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/finance')->middleware($middleware)->name('api.v1.finance.')->group(function () use ($requires): void {
    Route::middleware($requires(FinancePermission::ACCOUNTS_VIEW))->group(function (): void {
        Route::get('accounts', [FinanceController::class, 'accounts'])->name('accounts.index');
        Route::get('accounts/{account}', [FinanceController::class, 'showAccount'])->whereNumber('account')->name('accounts.show');
        Route::get('accounts/{account}/balance', [FinanceController::class, 'accountBalance'])->whereNumber('account')->name('accounts.balance');
        Route::get('lookups', [FinanceConfigurationController::class, 'lookups'])->name('lookups');
    });

    Route::middleware($requires(FinancePermission::ACCOUNTS_MANAGE))->group(function (): void {
        Route::post('accounts', [FinanceController::class, 'createAccount'])->name('accounts.store');
        Route::patch('accounts/{account}', [FinanceController::class, 'updateAccount'])->whereNumber('account')->name('accounts.update');
    });

    Route::middleware($requires(FinancePermission::POSTING_PROFILES_VIEW))->group(function (): void {
        Route::get('posting-profiles', [FinanceConfigurationController::class, 'postingProfiles'])->name('posting-profiles.index');
        Route::get('account-roles', [FinanceConfigurationController::class, 'accountRoles'])->name('account-roles.index');
        Route::get('account-assignments', [FinanceConfigurationController::class, 'accountAssignments'])->name('account-assignments.index');
    });
    Route::middleware($requires(FinancePermission::POSTING_PROFILES_MANAGE))->group(function (): void {
        Route::post('posting-profiles', [FinanceConfigurationController::class, 'createPostingProfile'])->name('posting-profiles.store');
        Route::patch('posting-profiles/{profile}', [FinanceConfigurationController::class, 'updatePostingProfile'])
            ->whereNumber('profile')
            ->name('posting-profiles.update');
        Route::post('account-roles', [FinanceConfigurationController::class, 'createAccountRole'])->name('account-roles.store');
        Route::patch('account-roles/{role}', [FinanceConfigurationController::class, 'updateAccountRole'])
            ->whereNumber('role')
            ->name('account-roles.update');
        Route::post('account-assignments', [FinanceConfigurationController::class, 'createAccountAssignment'])->name('account-assignments.store');
        Route::post('account-assignments/{assignment}/end', [FinanceConfigurationController::class, 'endAccountAssignment'])
            ->whereNumber('assignment')
            ->name('account-assignments.end');
    });

    Route::middleware($requires(FinancePermission::JOURNALS_VIEW))->group(function (): void {
        Route::get('journals', [FinanceController::class, 'journals'])->name('journals.index');
        Route::get('journals/{journal}', [FinanceController::class, 'showJournal'])->whereNumber('journal')->name('journals.show');
    });
    Route::post('journals', [FinanceController::class, 'createJournal'])
        ->middleware($requires(FinancePermission::JOURNALS_CREATE))
        ->name('journals.store');
    Route::patch('journals/{journal}', [FinanceController::class, 'updateJournal'])
        ->whereNumber('journal')
        ->middleware($requires(FinancePermission::JOURNALS_UPDATE))
        ->name('journals.update');
    Route::post('journals/{journal}/cancel', [FinanceController::class, 'cancelJournal'])
        ->whereNumber('journal')
        ->middleware($requires(FinancePermission::JOURNALS_CANCEL))
        ->name('journals.cancel');
    Route::post('journals/{journal}/post', [FinanceController::class, 'postJournal'])
        ->whereNumber('journal')
        ->middleware($requires(FinancePermission::JOURNALS_POST))
        ->name('journals.post');
    Route::post('journals/{journal}/reverse', [FinanceController::class, 'reverseJournal'])
        ->whereNumber('journal')
        ->middleware($requires(FinancePermission::JOURNALS_REVERSE))
        ->name('journals.reverse');

    Route::middleware($requires(FinancePermission::REPORTS_VIEW))->group(function (): void {
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
    });

    Route::post('currency-revaluations', [FinanceController::class, 'postCurrencyRevaluation'])
        ->middleware($requires(FinancePermission::CURRENCY_REVALUATIONS_POST))
        ->name('currency-revaluations.store');

    Route::middleware($requires(FinancePermission::FISCAL_CALENDAR_VIEW))->group(function (): void {
        Route::get('fiscal-years', [FinanceController::class, 'fiscalYears'])->name('fiscal-years.index');
        Route::get('fiscal-periods', [FinanceController::class, 'fiscalPeriods'])->name('fiscal-periods.index');
    });
    Route::patch('fiscal-years/{year}/status', [FinanceController::class, 'updateFiscalYearStatus'])
        ->whereNumber('year')
        ->middleware($requires(FinancePermission::FISCAL_CALENDAR_MANAGE))
        ->name('fiscal-years.status');
    Route::patch('fiscal-periods/{period}/status', [FinanceController::class, 'updateFiscalPeriodStatus'])
        ->whereNumber('period')
        ->middleware($requires(FinancePermission::FISCAL_CALENDAR_MANAGE))
        ->name('fiscal-periods.status');

    Route::middleware($requires(FinancePermission::BANK_RECONCILIATIONS_VIEW))->group(function (): void {
        Route::get('bank-reconciliations', [FinanceController::class, 'bankReconciliations'])->name('bank-reconciliations.index');
        Route::get('bank-reconciliations/{reconciliation}', [FinanceController::class, 'showBankReconciliation'])
            ->whereNumber('reconciliation')
            ->name('bank-reconciliations.show');
    });
    Route::middleware($requires(FinancePermission::BANK_RECONCILIATIONS_MANAGE))->group(function (): void {
        Route::post('bank-reconciliations', [FinanceController::class, 'createBankReconciliation'])->name('bank-reconciliations.store');
        Route::post('bank-reconciliations/{reconciliation}/complete', [FinanceController::class, 'completeBankReconciliation'])
            ->whereNumber('reconciliation')
            ->name('bank-reconciliations.complete');
        Route::post('bank-reconciliations/{reconciliation}/lines/{line}/match', [FinanceController::class, 'matchBankStatementLine'])
            ->whereNumber(['reconciliation', 'line'])
            ->name('bank-reconciliations.lines.match');
        Route::post('bank-reconciliations/{reconciliation}/lines/{line}/unmatch', [FinanceController::class, 'unmatchBankStatementLine'])
            ->whereNumber(['reconciliation', 'line'])
            ->name('bank-reconciliations.lines.unmatch');
    });

    Route::middleware($requires(FinancePermission::BUDGETS_VIEW))->group(function (): void {
        Route::get('budgets', [FinanceController::class, 'budgets'])->name('budgets.index');
        Route::get('budgets/{budget}', [FinanceController::class, 'showBudget'])->whereNumber('budget')->name('budgets.show');
        Route::get('budgets/{budget}/actuals', [FinanceController::class, 'budgetActuals'])->whereNumber('budget')->name('budgets.actuals');
    });
    Route::middleware($requires(FinancePermission::BUDGETS_MANAGE))->group(function (): void {
        Route::post('budgets', [FinanceController::class, 'createBudget'])->name('budgets.store');
        Route::patch('budgets/{budget}', [FinanceController::class, 'updateBudget'])->whereNumber('budget')->name('budgets.update');
    });
});
