<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Presentation\Http\Controllers\AccountController;
use Modules\Finance\Presentation\Http\Controllers\ApTransactionController;
use Modules\Finance\Presentation\Http\Controllers\ArTransactionController;
use Modules\Finance\Presentation\Http\Controllers\BankAccountController;
use Modules\Finance\Presentation\Http\Controllers\BankCategoryRuleController;
use Modules\Finance\Presentation\Http\Controllers\BankReconciliationController;
use Modules\Finance\Presentation\Http\Controllers\BankTransactionController;
use Modules\Finance\Presentation\Http\Controllers\BudgetController;
use Modules\Finance\Presentation\Http\Controllers\BudgetLineController;
use Modules\Finance\Presentation\Http\Controllers\CostCenterController;
use Modules\Finance\Presentation\Http\Controllers\FiscalPeriodController;
use Modules\Finance\Presentation\Http\Controllers\FiscalYearController;
use Modules\Finance\Presentation\Http\Controllers\JournalEngineController;
use Modules\Finance\Presentation\Http\Controllers\JournalEntryController;
use Modules\Finance\Presentation\Http\Controllers\JournalEntryLineController;
use Modules\Finance\Presentation\Http\Controllers\PaymentTermController;
use Modules\Finance\Presentation\Http\Controllers\TaxGroupController;
use Modules\Finance\Presentation\Http\Controllers\TaxRateController;
use Modules\Finance\Presentation\Http\Controllers\TaxRuleController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/finance')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('finance.')
    ->group(function (): void {
        Route::post('journal-entries/{journalEntry}/engines/post', [JournalEngineController::class, 'post'])
            ->name('journal-entries.engines.post');
        Route::post(
            'journal-entries/{journalEntry}/engines/preview-posting',
            [JournalEngineController::class, 'previewPost']
        )
            ->name('journal-entries.engines.preview-posting');
        Route::post('journal-entries/{journalEntry}/engines/reverse', [JournalEngineController::class, 'reverse'])
            ->name('journal-entries.engines.reverse');
        Route::post('posting-preview', [JournalEngineController::class, 'previewSourcePosting'])
            ->name('posting-preview');
        Route::post('tax/preview-calculate', [TaxRateController::class, 'previewCalculation'])
            ->name('tax.preview-calculate');
        Route::get('accounts/tree', [AccountController::class, 'tree'])->name('accounts.tree');
        Route::patch('accounts/{account}/activate', [AccountController::class, 'activate'])->name('accounts.activate');
        Route::patch('accounts/{account}/deactivate', [AccountController::class, 'deactivate'])->name('accounts.deactivate');
        Route::patch('fiscal-periods/{fiscalPeriod}/open', [FiscalPeriodController::class, 'open'])->name('fiscal-periods.open');
        Route::patch('fiscal-periods/{fiscalPeriod}/close', [FiscalPeriodController::class, 'close'])->name('fiscal-periods.close');
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('fiscal-years', FiscalYearController::class);
        Route::apiResource('fiscal-periods', FiscalPeriodController::class);
        Route::apiResource('payment-terms', PaymentTermController::class);
        Route::apiResource('tax-groups', TaxGroupController::class);
        Route::apiResource('tax-rates', TaxRateController::class);
        Route::apiResource('tax-rules', TaxRuleController::class);
        Route::apiResource('ap-transactions', ApTransactionController::class);
        Route::apiResource('ar-transactions', ArTransactionController::class);
        Route::apiResource('cost-centers', CostCenterController::class);
        Route::apiResource('journal-entries', JournalEntryController::class);
        Route::apiResource('journal-entry-lines', JournalEntryLineController::class);
        Route::apiResource('budgets', BudgetController::class);
        Route::apiResource('budget-lines', BudgetLineController::class);
        Route::apiResource('bank-accounts', BankAccountController::class);
        Route::apiResource('bank-category-rules', BankCategoryRuleController::class);
        Route::apiResource('bank-transactions', BankTransactionController::class);
        Route::apiResource('bank-reconciliations', BankReconciliationController::class);
    });
